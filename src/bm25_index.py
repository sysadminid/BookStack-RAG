from whoosh.fields import Schema, ID, TEXT, KEYWORD, DATETIME
from whoosh import index
from whoosh.qparser import MultifieldParser, OrGroup
from whoosh.analysis import StemmingAnalyzer
import os, shutil
from datetime import datetime

SCHEMA = Schema(
    doc_id=ID(stored=True, unique=True),   # chunk id
    title=TEXT(stored=True, analyzer=StemmingAnalyzer()),
    text=TEXT(stored=True, analyzer=StemmingAnalyzer()),
    book=ID(stored=True),
    chapter=ID(stored=True),
    tags=KEYWORD(stored=True, commas=True, lowercase=True),
    url=ID(stored=True),
    updated_at=DATETIME(stored=True)
)

def get_or_create_index():
    path = "./bm25_index"
    if not os.path.exists(path):
        os.makedirs(path, exist_ok=True)
        ix = index.create_in(path, SCHEMA)
    else:
        ix = index.open_dir(path)
    return ix

def rebuild(docs):
    path = "./bm25_index"
    if os.path.exists(path): shutil.rmtree(path)
    ix = get_or_create_index()
    with ix.writer(limitmb=256) as w:
        for d in docs:
            w.update_document(
                doc_id=d["doc_id"],
                title=d["title"] or "",
                text=d["text"] or "",
                book=d["book_slug"] or "",
                chapter=d.get("chapter_slug") or "",
                tags=",".join((d.get("tags") or "").split(",")) if d.get("tags") else "",
                url=d["url"],
                updated_at=d["updated_at"]
            )
    return ix

def search(query, limit=20, filters=None):
    ix = get_or_create_index()
    qp = MultifieldParser(["title","text"], schema=ix.schema, group=OrGroup)
    q = qp.parse(query)
    with ix.searcher() as s:
        results = s.search(q, limit=limit)
        rows = []
        for r in results:
            ok = True
            if filters:
                if "book" in filters and r["book"] != filters["book"]:
                    ok = False
                if "chapter" in filters and r["chapter"] != filters["chapter"]:
                    ok = False
                if "tag" in filters and filters["tag"] and (filters["tag"] not in (r["tags"] or "")):
                    ok = False
            if ok:
                d = dict(r)
                d["score"] = float(r.score)  # <-- include score for hybrid
                rows.append(d)
        return rows
