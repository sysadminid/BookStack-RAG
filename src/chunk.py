import os
from typing import List
from transformers import AutoTokenizer

# Large cap used ONLY for chunking to avoid warnings on raw text
_CHUNKING_MAX_LEN = 10**9

def get_tokenizer():
    model = os.getenv("EMBED_MODEL", "sentence-transformers/all-MiniLM-L6-v2")
    tok = AutoTokenizer.from_pretrained(model, use_fast=True)
    tok.model_max_length = _CHUNKING_MAX_LEN
    try:
        tok.deprecation_warnings["sequence_length_is_longer_than"] = True  # type: ignore
    except Exception:
        pass
    return tok

def chunk_text(text: str, target_tokens: int = 480, overlap: int = 100) -> List[str]:
    """
    Token-level chunking. We intentionally DON'T use the 512 cap here so we can
    tokenize the *full* doc without warnings, then slice to safe windows.
    The embed step still enforces 512 via hard truncation.
    """
    tok = get_tokenizer()
    ids = tok.encode(text or "", add_special_tokens=False)
    chunks = []
    step = max(target_tokens - overlap, 1)
    for i in range(0, len(ids), step):
        window = ids[i:i + target_tokens]
        chunks.append(tok.decode(window, skip_special_tokens=True))
    return chunks

