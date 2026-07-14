"""Конвейер прайсов: парсинг XLSX по профилю поставщика → supplier_prices (с историей)
→ матчинг по артикулу (exact → analog → fuzzy) → пересчёт offers.

Замена legacy `xml.php`: матчинг по АРТИКУЛУ производителя (а не по коду 1С),
per-supplier профили колонок и наценки, история цен без TRUNCATE."""
import io
from datetime import datetime, timezone
from openpyxl import load_workbook
from . import db

FUZZY_THRESHOLD = 0.62  # порог pg_trgm для авто-сопоставления-кандидата (в очередь модерации)

# Идемпотентная миграция: помечаем происхождение offer и уникальность (товар, поставщик).
MIGRATION = """
ALTER TABLE offers ADD COLUMN IF NOT EXISTS source text DEFAULT 'legacy';
CREATE UNIQUE INDEX IF NOT EXISTS ux_offers_prod_sup
  ON offers(product_id, supplier_id) WHERE source='price';
"""


async def ensure_migrated():
    async with db.pool.connection() as conn:
        await conn.execute(MIGRATION)


def parse_xlsx(data: bytes, profile: dict) -> list[dict]:
    """Читает XLSX по профилю поставщика. profile: sheet_index, header_rows,
    col_maker/col_name/col_article/col_price/col_qty/col_code (0-based индексы)."""
    wb = load_workbook(io.BytesIO(data), read_only=True, data_only=True)
    ws = wb.worksheets[profile.get("sheet_index", 0)]
    header = profile.get("header_rows", 7)
    rows = []

    def cell(row, idx):
        if idx is None or idx < 0 or idx >= len(row):
            return None
        v = row[idx]
        return v if v is not None else None

    for i, row in enumerate(ws.iter_rows(values_only=True)):
        if i < header:
            continue
        art = cell(row, profile.get("col_article"))
        price = cell(row, profile.get("col_price"))
        if art in (None, "") and price in (None, ""):
            continue
        rows.append({
            "maker": _s(cell(row, profile.get("col_maker"))),
            "name": _s(cell(row, profile.get("col_name"))),
            "article": _s(art),
            "price": _f(price),
            "qty": _f(cell(row, profile.get("col_qty"))),
            "external_code": _s(cell(row, profile.get("col_code"))),
        })
    wb.close()
    return rows


async def ingest(supplier_id: int, rows: list[dict]) -> dict:
    """Пишет строки прайса (история), матчит по артикулу, пересчитывает offers."""
    await ensure_migrated()
    batch = datetime.now(timezone.utc)
    async with db.pool.connection() as conn:
        async with conn.cursor() as cur:
            for r in rows:
                await cur.execute(
                    """INSERT INTO supplier_prices
                       (supplier_id, article, external_code, name, maker, price, qty, received_at)
                       VALUES (%s,%s,%s,%s,%s,%s,%s,%s)""",
                    (supplier_id, r["article"], r["external_code"], r["name"],
                     r["maker"], r["price"], r["qty"], batch))

            # --- матчинг только что загруженной партии ---
            # 1) точное совпадение по нормализованному артикулу
            await cur.execute("""
                UPDATE supplier_prices sp SET matched_product_id=p.id,
                       match_method='exact_article', match_confidence=1.0
                FROM products p
                WHERE sp.supplier_id=%s AND sp.received_at=%s AND sp.matched_product_id IS NULL
                  AND sp.normalized_article IS NOT NULL
                  AND p.normalized_article=sp.normalized_article""", (supplier_id, batch))
            # 2) через таблицу аналогов
            await cur.execute("""
                UPDATE supplier_prices sp SET matched_product_id=a.product_id,
                       match_method='analog', match_confidence=0.8
                FROM analogs a
                WHERE sp.supplier_id=%s AND sp.received_at=%s AND sp.matched_product_id IS NULL
                  AND sp.normalized_article IS NOT NULL
                  AND a.normalized_article=sp.normalized_article""", (supplier_id, batch))
            # 3) нечёткий (pg_trgm) — только кандидат в очередь модерации (не в offers)
            await cur.execute("""
                UPDATE supplier_prices sp SET matched_product_id=m.pid,
                       match_method='fuzzy', match_confidence=m.sim
                FROM (
                    SELECT spid, pid, sim FROM (
                        SELECT sp2.id AS spid, p.id AS pid,
                               similarity(p.normalized_article, sp2.normalized_article) AS sim,
                               row_number() OVER (PARTITION BY sp2.id
                                   ORDER BY similarity(p.normalized_article, sp2.normalized_article) DESC) AS rn
                        FROM supplier_prices sp2
                        JOIN products p ON p.normalized_article %% sp2.normalized_article
                        WHERE sp2.supplier_id=%s AND sp2.received_at=%s
                          AND sp2.matched_product_id IS NULL AND sp2.normalized_article IS NOT NULL
                    ) ranked WHERE rn=1
                ) m
                WHERE sp.id=m.spid AND m.sim >= %s""",
                (supplier_id, batch, FUZZY_THRESHOLD))

            # --- пересчёт offers из точных/аналоговых матчей (fuzzy — на модерацию) ---
            await cur.execute("DELETE FROM offers WHERE supplier_id=%s AND source='price'", (supplier_id,))
            await cur.execute("""
                INSERT INTO offers (product_id, supplier_id, article, external_code,
                                    price, qty, in_stock, source, updated_at)
                SELECT DISTINCT ON (sp.matched_product_id)
                       sp.matched_product_id, sp.supplier_id, sp.article, sp.external_code,
                       ceil(sp.price*(1+s.markup_percent/100)/s.price_round)*s.price_round,
                       sp.qty, COALESCE(sp.qty,0) > 0, 'price', now()
                FROM supplier_prices sp JOIN suppliers s ON s.id=sp.supplier_id
                WHERE sp.supplier_id=%s AND sp.received_at=%s
                  AND sp.matched_product_id IS NOT NULL
                  AND sp.match_method IN ('exact_article','analog')
                  AND sp.price IS NOT NULL
                ORDER BY sp.matched_product_id, sp.price ASC
                ON CONFLICT (product_id, supplier_id) WHERE source='price'
                DO UPDATE SET price=EXCLUDED.price, qty=EXCLUDED.qty,
                              in_stock=EXCLUDED.in_stock, article=EXCLUDED.article,
                              external_code=EXCLUDED.external_code, updated_at=now()
            """, (supplier_id, batch))

            stats = {}
            await cur.execute(
                """SELECT COALESCE(match_method,'none') AS mm, count(*) AS n
                   FROM supplier_prices WHERE supplier_id=%s AND received_at=%s GROUP BY 1""",
                (supplier_id, batch))
            for row in await cur.fetchall():
                stats[row["mm"]] = row["n"]
    return {"rows": len(rows), "match": stats}


def _s(v):
    if v is None:
        return None
    s = str(v).strip()
    return s or None


def _f(v):
    if v in (None, ""):
        return None
    try:
        return float(str(v).replace(",", ".").replace(" ", "").replace("\xa0", ""))
    except (ValueError, TypeError):
        return None
