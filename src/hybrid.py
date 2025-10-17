import os
from typing import List, Dict, Optional, Tuple
from src.embed_store import get_chroma, load_embedder, embed_texts
from src.bm25_index import search as bm25_search

def rrf_fuse(*ranked_lists: List[List[Tuple[str,float]]], k: int = 60) -> List[Tuple[str, float]]:
    scores = {}
    for lst in ranked_lists:
        for rank, (doc_id, _s) in enumerate(lst, start=1):
            scores[doc_id] = scores.get(doc_id, 0.0) + 1.0/(k + rank)
    return sorted(scores.items(), key=lambda x: x[1], reverse=True)

def semantic_search(query: str, k: int, filters: Dict):
    coll = get_chroma()
    model = load_embedder()
    emb = embed_texts([query], model, is_query=True)[0]

    # Build compliant filter for Chroma 0.5.x
    clauses = []
    if filters.get("book"):
        clauses.append({"book_slug": {"$eq": filters["book"]}})
    if filters.get("chapter"):
        clauses.append({"chapter_slug": {"$eq": filters["chapter"]}})
    if filters.get("tag"):
        # 'tags' is a comma-joined string; use $contains
        clauses.append({"tags": {"$contains": filters["tag"]}})

    if not clauses:
        where = None
    elif len(clauses) == 1:
        where = clauses[0]
    else:
        where = {"$and": clauses}

    res = coll.query(
        query_embeddings=[emb],
        n_results=k,
        where=where,
        include=["documents", "metadatas", "distances"],
    )

    items, payload = [], {}
    if res.get("ids") and res["ids"]:
        for i, doc_id in enumerate(res["ids"][0]):
            score = 1.0 - float(res["distances"][0][i]) if res.get("distances") else 0.0
            items.append((doc_id, score))
            payload[doc_id] = (res["documents"][0][i], res["metadatas"][0][i])
    return items, payload

def keyword_search(query: str, k: int, filters: Dict):
    rows = bm25_search(query, limit=k, filters=filters)
    items = [(r["doc_id"], r.get("score", 0.0)) for r in rows]
    payload = {r["doc_id"]: (r["text"], {
        "page_title": r["title"], "book_slug": r["book"], "chapter_slug": r["chapter"],
        "tags": r["tags"], "url": r["url"]
    }) for r in rows}
    return items, payload

def hybrid_search(query: str, k=8, filters: Optional[Dict]=None, rrf_k=60, reranker=None):
    filters = filters or {}
    kw_items, kw_payload = keyword_search(query, k*3, filters)
    sem_items, sem_payload = semantic_search(query, k*3, filters)

    fused = rrf_fuse(kw_items, sem_items, k=rrf_k)[:k*3]
    docs = []
    for doc_id, _ in fused:
        if doc_id in sem_payload:
            text, meta = sem_payload[doc_id]
        else:
            text, meta = kw_payload.get(doc_id, ("", {}))
        docs.append({"id": doc_id, "text": text, "meta": meta})

    if reranker:
        pairs = [(query, d["text"]) for d in docs]
        scores = reranker.predict(pairs)
        docs = [d for _, d in sorted(zip(scores, docs), key=lambda x: x[0], reverse=True)]
    return docs[:k]
