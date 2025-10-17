import os, json
from datetime import datetime
from dotenv import load_dotenv
from src.db import fetch_pages
from src.clean import html_to_text
from src.chunk import chunk_text
from src.embed_store import load_embedder, get_chroma, embed_texts
from src.utils import checksum, page_url
from src.bm25_index import rebuild as rebuild_bm25
from src.bm25_index import get_or_create_index

load_dotenv()

def page_to_docs(p):
    text = html_to_text(p["html"])
    if not text.strip():
        return []
    chunks = chunk_text(text, target_tokens=500, overlap=100)
    docs = []
    for i, ch in enumerate(chunks):
        doc_id = f'page-{p["page_id"]}-ch-{i}'
        docs.append({
            "id": doc_id,
            "text": ch,
            "meta": {
                "page_id": p["page_id"],
                "page_title": p["page_title"],
                "page_slug": p["page_slug"],
                "book_slug": p["book_slug"],
                "book_title": p["book_title"],
                "chapter_slug": p.get("chapter_slug"),
                "chapter_title": p.get("chapter_title"),
                "tags": p.get("tags") or "",
                "updated_at": str(p["updated_at"]),
                "url": page_url(p["book_slug"], p["page_slug"], p.get("chapter_slug")),
                "page_checksum": checksum(text)
            }
        })
    return docs

def full_ingest():
    model = load_embedder()
    coll = get_chroma()
    pages = fetch_pages()
    all_docs = []
    for p in pages:
        all_docs += page_to_docs(p)

    # Upsert to Chroma
    ids = [d["id"] for d in all_docs]
    texts = [d["text"] for d in all_docs]
    metas = [d["meta"] for d in all_docs]
    embs = embed_texts(texts, model, is_query=False)
    coll.upsert(ids=ids, documents=texts, embeddings=embs, metadatas=metas)

    # Build BM25 index
    bm_docs = [{
        "doc_id": d["id"],
        "title": d["meta"]["page_title"],
        "text": d["text"],
        "book_slug": d["meta"]["book_slug"],
        "chapter_slug": d["meta"]["chapter_slug"],
        "tags": d["meta"]["tags"],
        "url": d["meta"]["url"],
        "updated_at": datetime.fromisoformat(d["meta"]["updated_at"])
    } for d in all_docs]
    rebuild_bm25(bm_docs)
    return {"pages": len(pages), "chunks": len(all_docs)}

def incremental_ingest(updated_after_iso: str):
    model = load_embedder()
    coll = get_chroma()
    pages = fetch_pages(updated_after=updated_after_iso)
    if not pages: return {"pages":0, "chunks":0}

    docs = []
    for p in pages:
        docs += page_to_docs(p)
    ids = [d["id"] for d in docs]
    texts = [d["text"] for d in docs]
    metas = [d["meta"] for d in docs]
    embs = embed_texts(texts, model, is_query=False)
    coll.upsert(ids=ids, documents=texts, embeddings=embs, metadatas=metas)

    return {"pages": len(pages), "chunks": len(docs)}
