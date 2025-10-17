from bs4 import BeautifulSoup
import lxml
import re

def _pick_parser():
    try:
        return "lxml"
    except Exception:
        return "html.parser"

def html_to_text(html: str) -> str:
    markup = (html or "").strip()
    if not markup:
        return ""

    soup = BeautifulSoup(markup, _pick_parser())
    for bad in soup(["script", "style", "noscript"]):
        bad.decompose()
    text = soup.get_text(separator="\n")
    text = re.sub(r"\n{3,}", "\n\n", text)
    text = re.sub(r"[ \t]{2,}", " ", text).strip()
    return text

