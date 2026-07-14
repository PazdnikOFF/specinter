"""Meilisearch: индекс каталога и переиндексация из Postgres."""
import meilisearch
from .config import MEILI_URL, MEILI_KEY, PRODUCTS_INDEX
from . import db

client = meilisearch.Client(MEILI_URL, MEILI_KEY)


def ensure_index():
    try:
        client.create_index(PRODUCTS_INDEX, {"primaryKey": "id"})
    except Exception:
        pass
    idx = client.index(PRODUCTS_INDEX)
    idx.update_settings({
        # поиск по артикулу, названию, бренду и артикулам аналогов
        "searchableAttributes": ["manufacturer_article", "name", "brand", "analog_articles"],
        "filterableAttributes": ["brand", "in_stock", "category_ids"],
        "sortableAttributes": ["min_price"],
        # sort первым правилом → результаты ВСЕГДА по цене (возрастающе), затем релевантность
        "rankingRules": ["sort", "words", "typo", "proximity", "attribute", "exactness"],
        "typoTolerance": {"minWordSizeForTypos": {"oneTypo": 4, "twoTypos": 8}},
    })
    return idx


async def reindex() -> int:
    """Собирает документы товаров из Postgres и грузит в Meilisearch."""
    rows = await db.fetch("""
        SELECT p.id, p.manufacturer_article, p.name, p.brand, p.slug, p.primary_image,
               COALESCE(array_agg(DISTINCT a.analog_article)
                        FILTER (WHERE a.analog_article IS NOT NULL), '{}') AS analog_articles,
               COALESCE(array_agg(DISTINCT pc.category_id)
                        FILTER (WHERE pc.category_id IS NOT NULL), '{}') AS category_ids,
               MIN(o.price) FILTER (WHERE o.price IS NOT NULL) AS min_price,
               BOOL_OR(o.in_stock) AS in_stock
        FROM products p
        LEFT JOIN analogs a ON a.product_id = p.id
        LEFT JOIN product_categories pc ON pc.product_id = p.id
        LEFT JOIN offers o ON o.product_id = p.id
        WHERE p.visible
        GROUP BY p.id
    """)
    docs = []
    for r in rows:
        d = dict(r)
        d["min_price"] = float(d["min_price"]) if d["min_price"] is not None else None
        docs.append(d)
    idx = ensure_index()
    # батчами
    BATCH = 5000
    for i in range(0, len(docs), BATCH):
        idx.add_documents(docs[i:i + BATCH])
    return len(docs)


def search(q: str, limit: int = 20, offset: int = 0, in_stock: bool | None = None):
    idx = client.index(PRODUCTS_INDEX)
    # Всегда по цене от меньшего к большему (позиции без цены — в конце).
    params = {"limit": limit, "offset": offset, "sort": ["min_price:asc"]}
    if in_stock is not None:
        params["filter"] = f"in_stock = {str(in_stock).lower()}"
    return idx.search(q, params)
