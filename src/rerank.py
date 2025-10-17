import os
from sentence_transformers.cross_encoder import CrossEncoder

def load_reranker():
    name = os.getenv("RERANKER_MODEL")
    if not name: return None
    return CrossEncoder(name)
