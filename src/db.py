from sqlalchemy import create_engine, text
import os

def sql_url():
    host = os.getenv("BOOKSTACK_DB_HOST")
    user = os.getenv("BOOKSTACK_DB_USER")
    pw   = os.getenv("BOOKSTACK_DB_PASS")
    db   = os.getenv("BOOKSTACK_DB_NAME")
    return f"mysql+pymysql://{user}:{pw}@{host}/{db}?charset=utf8mb4"

def _detect_page_entity_type(engine):
    """
    Detect how BookStack stores page tag entity_type.
    Common values: 'page', 'book_page', '\\BookStack\\Entities\\Page' (older).
    Falls back to 'page' if nothing found.
    """
    # try the most likely values in order
    candidates = ["page", "book_page", "BookStack\\Page", "\\BookStack\\Entities\\Page", "Page"]
    with engine.connect() as conn:
        # If there are no tags at all, just return default 'page'
        total_tags = conn.execute(text("SELECT COUNT(*) FROM tags")).scalar()
        if not total_tags:
            return "page"
        # Probe which candidate exists
        for c in candidates:
            found = conn.execute(text("SELECT 1 FROM tags WHERE entity_type = :t LIMIT 1"), {"t": c}).first()
            if found:
                return c
        # As a last resort, pick the most frequent entity_type that seems like page-level
        row = conn.execute(text("""
            SELECT entity_type, COUNT(*) as cnt
            FROM tags
            GROUP BY entity_type
            ORDER BY cnt DESC
            LIMIT 1
        """)).first()
        return row[0] if row else "page"

def fetch_pages(updated_after=None, limit=None):
    engine = create_engine(sql_url())
    entity_type_page = _detect_page_entity_type(engine)

    where = "p.draft = 0"
    params = {"entity_type_page": entity_type_page}
    if updated_after:
        where += " AND p.updated_at >= :updated_after"
        params["updated_after"] = updated_after
    lim = "LIMIT :lim" if limit else ""
    if limit:
        params["lim"] = limit

    # Tags are aggregated from the single `tags` table by matching entity_type + entity_id
    q = f"""
    SELECT
        p.id                               AS page_id,
        p.name                             AS page_title,
        p.slug                             AS page_slug,
        p.html,
        p.updated_at,
        p.book_id,
        p.chapter_id,
        b.name                             AS book_title,
        b.slug                             AS book_slug,
        c.name                             AS chapter_title,
        c.slug                             AS chapter_slug,
        -- Aggregate tags of the page; example output: "status:approved,area:hr,owner:alice"
        (
            SELECT GROUP_CONCAT(CONCAT(t.name, COALESCE(CONCAT(':', t.value), '')) SEPARATOR ',')
            FROM tags t
            WHERE t.entity_type = :entity_type_page
              AND t.entity_id   = p.id
        ) AS tags
    FROM pages p
    JOIN books b      ON b.id = p.book_id
    LEFT JOIN chapters c ON c.id = p.chapter_id
    WHERE {where}
    GROUP BY p.id
    {lim}
    """

    with engine.connect() as conn:
        return [dict(r._mapping) for r in conn.execute(text(q), params)]

