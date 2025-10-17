import os
import chromadb
from typing import List
import torch
from sentence_transformers import SentenceTransformer
from transformers import AutoTokenizer

DEVICE = "cuda" if torch.cuda.is_available() else "cpu"
MAX_LEN = 512  # XLM-R window used by multilingual-e5-base

def load_embedder():
    model_name = os.getenv("EMBED_MODEL", "sentence-transformers/all-MiniLM-L6-v2")
    model = SentenceTransformer(model_name, device=DEVICE)
    model.max_seq_length = MAX_LEN
    tok = AutoTokenizer.from_pretrained(model_name, use_fast=True)
    tok.model_max_length = MAX_LEN
    model._ext_tokenizer = tok
    model._ext_model_name = model_name
    return model

def get_chroma():
    client = chromadb.PersistentClient(path=os.getenv("CHROMA_DIR", ".chroma"))
    return client.get_or_create_collection("bookstack")

def _with_e5_prefix(texts: List[str], model_name: str, is_query: bool) -> List[str]:
    if model_name.startswith("sentence-transformers/all-MiniLM-L6-v2"):
        pref = "query: " if is_query else "passage: "
        return [pref + (t or "") for t in texts]
    return texts

@torch.no_grad()
def embed_texts(texts: List[str], model: SentenceTransformer, is_query=False) -> List[List[float]]:
    """
    Explicitly tokenize with truncation before passing into the SentenceTransformer.
    This bypasses the internal tokenization path that can emit 512+ warnings.
    """
    model_name = getattr(model, "_ext_model_name", os.getenv("EMBED_MODEL", "sentence-transformers/all-MiniLM-L6-v2"))
    tok = getattr(model, "_ext_tokenizer", AutoTokenizer.from_pretrained(model_name, use_fast=True))
    tok.model_max_length = MAX_LEN

    texts = _with_e5_prefix(texts, model_name, is_query)

    batch = tok(
        texts,
        padding=True,
        truncation=True,
        max_length=MAX_LEN,
        return_tensors="pt",
    )

    for k in batch:
        batch[k] = batch[k].to(model.device)

    # Run the ST pipeline manually; this returns a dict with "sentence_embedding"
    features = {"input_ids": batch["input_ids"], "attention_mask": batch["attention_mask"]}
    if "token_type_ids" in batch:
        features["token_type_ids"] = batch["token_type_ids"]

    out = model(features)  # SentenceTransformer.__call__ runs modules -> pooling -> normalize
    emb = out["sentence_embedding"]          # (bsz, dim) tensor
    return emb.cpu().tolist()

