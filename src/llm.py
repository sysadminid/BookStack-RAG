import os
import re
from openai import AzureOpenAI
from tenacity import retry, wait_exponential, stop_after_attempt
from typing import List, Dict

# --- Client init ---
def client() -> AzureOpenAI:
    return AzureOpenAI(
        api_key=os.getenv("AZURE_OPENAI_API_KEY"),
        azure_endpoint=os.getenv("AZURE_OPENAI_ENDPOINT"),
        api_version=os.getenv("AZURE_OPENAI_API_VERSION"),
    )

SYSTEM_PROMPT = """You are a helpful assistant. Use ONLY the provided context chunks to answer.
- If unsure or context is insufficient, say you don't know and suggest where to look.
- Cite sources with bracketed numeric markers like [1], [2], ... (NO hash signs).
- Each marker must correspond to the same index in the provided 'sources' list.
- Keep answers concise and actionable."""


def _extract_urls_from_sources(sources):
    """Each source string looks like 'Title · Book · https://...'.
    We return a list of just the URLs in order, keeping 1-index alignment."""
    urls = []
    for s in sources:
        parts = str(s).split(" · ")
        url = parts[-1].strip() if parts else ""
        urls.append(url)
    return urls

def _normalize_citation_markers(text):
    """Convert [#1] or [ #1 ] etc into [1]."""
    return re.sub(r"\[\s*#\s*(\d+)\s*\]", r"[\1]", text)

def _linkify_citations(markdown_text, sources):
    """
    Replace [1], [2] ... in the markdown with [1](URL), [2](URL) etc.
    We ONLY link references that have a valid URL at that index.
    """
    urls = _extract_urls_from_sources(sources)

    def repl(m):
        idx = int(m.group(1))
        if 1 <= idx <= len(urls) and urls[idx-1]:
            return f"[{idx}]({urls[idx-1]})"
        return m.group(0)

    text = _normalize_citation_markers(markdown_text)
    
    return re.sub(r"\[(\d+)\]", repl, text)

def build_context(docs: List[Dict], max_chars=6000):
    ctx = []
    sources = []
    total = 0
    for i, d in enumerate(docs, start=1):
        meta = d["meta"]
        source = f'{meta.get("page_title","(untitled)")} · {meta.get("book_title","")} · {meta.get("url","")}'
        chunk = d["text"].strip()
        add = len(chunk)
        if total + add > max_chars:
            break
        total += add
        ctx.append(f"[{i}] {chunk}")
        sources.append(source)
    return "\n\n".join(ctx), sources

@retry(wait=wait_exponential(multiplier=1, min=1, max=8), stop=stop_after_attempt(3))
def answer(query: str, docs: List[Dict]):
    ctx, sources = build_context(docs)
    api = client()

    deployment = os.getenv("AZURE_OPENAI_DEPLOYMENT")
    if not deployment:
        raise RuntimeError("AZURE_OPENAI_DEPLOYMENT must be set in .env")

    messages = [
        {"role":"system","content":SYSTEM_PROMPT},
        {"role":"user","content":f"Question: {query}\n\nContext:\n{ctx}\n\nAnswer with citations like [1], [2]..."}
    ]

    resp = api.chat.completions.create(
        model=deployment,
        messages=messages,
        temperature=0.2,
        timeout=30,
    )
    raw = resp.choices[0].message.content or ""
    linked = _linkify_citations(raw, sources)
    return linked, sources
