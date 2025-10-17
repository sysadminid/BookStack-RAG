import hashlib, os

def checksum(s: str) -> str:
    return hashlib.sha256((s or "").encode("utf-8")).hexdigest()

def page_url(book_slug, page_slug, chapter_slug=None):
    base = os.getenv("BOOKSTACK_BASE_URL", "").rstrip("/")
    return f"{base}/books/{book_slug}/page/{page_slug}"
