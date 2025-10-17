# src/api/main.py
import os
from fastapi import FastAPI, Depends, HTTPException, Header
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from dotenv import load_dotenv
from typing import Optional, List, Dict
from src.hybrid import hybrid_search
from src.rerank import load_reranker
from src.ingest import full_ingest, incremental_ingest
from src.llm import answer

load_dotenv()
app = FastAPI(title="BookStack RAG API", version="1.0.0")

origins = os.getenv("CORS_ORIGINS","*")
app.add_middleware(
    CORSMiddleware,
    allow_origins=[o.strip() for o in origins.split(",")] if origins!="*" else ["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

def require_key(x_api_key: str = Header(None)):
    if x_api_key != os.getenv("API_KEY"):
        raise HTTPException(status_code=401, detail="Invalid API key")
    return True

class Filter(BaseModel):
    book: Optional[str] = None
    chapter: Optional[str] = None
    tag: Optional[str] = None

class SearchRequest(BaseModel):
    query: str
    k: int = 6
    filters: Optional[Filter] = None

class AskResponse(BaseModel):
    answer: str
    sources: List[str]

reranker = load_reranker()

@app.post("/retrieve")
def retrieve(req: SearchRequest, auth=Depends(require_key)):
    filt = req.filters.model_dump() if req.filters else {}
    docs = hybrid_search(req.query, k=req.k, filters=filt, reranker=reranker)
    return {"results": docs}

@app.post("/search", response_model=AskResponse)
def search_main(req: SearchRequest, auth=Depends(require_key)):
    filt = req.filters.model_dump() if req.filters else {}
    docs = hybrid_search(req.query, k=req.k, filters=filt, reranker=reranker)
    ans, sources = answer(req.query, docs)
    return {"answer": ans, "sources": sources}

class ReindexRequest(BaseModel):
    mode: str = "full" # or "incremental"
    updated_after: Optional[str] = None

@app.post("/ingest/reindex")
def reindex(req: ReindexRequest, auth=Depends(require_key)):
    if req.mode == "full":
        return full_ingest()
    elif req.mode == "incremental" and req.updated_after:
        return incremental_ingest(req.updated_after)
    else:
        raise HTTPException(400, "Provide mode=full or mode=incremental with updated_after")
