from fastapi import APIRouter, UploadFile, File, HTTPException, Query
from .. import db, prices, search

router = APIRouter(prefix="/api/admin", tags=["admin: prices"])

# Дефолтный профиль колонок из legacy xml.php (0-based):
# 0=maker, 1=name, 2=article, 4=price, 5=qty, 6=code(1С); шапка — 7 строк.
DEFAULT_PROFILE = {
    "sheet_index": 0, "header_rows": 7,
    "col_maker": 0, "col_name": 1, "col_article": 2,
    "col_price": 4, "col_qty": 5, "col_code": 6,
}


@router.get("/suppliers")
async def suppliers():
    return await db.fetch("SELECT * FROM suppliers ORDER BY id")


@router.post("/suppliers")
async def create_supplier(name: str, city: str | None = None,
                          sender_email: str | None = None, markup_percent: float = 15.0):
    row = await db.fetchone(
        """INSERT INTO suppliers (name, city, sender_email, markup_percent)
           VALUES (%s,%s,%s,%s) RETURNING *""",
        (name, city, sender_email, markup_percent))
    await db.fetchone(
        """INSERT INTO supplier_price_profiles
           (supplier_id, sheet_index, header_rows, col_maker, col_name, col_article,
            col_price, col_qty, col_code)
           VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s) RETURNING id""",
        (row["id"], DEFAULT_PROFILE["sheet_index"], DEFAULT_PROFILE["header_rows"],
         DEFAULT_PROFILE["col_maker"], DEFAULT_PROFILE["col_name"], DEFAULT_PROFILE["col_article"],
         DEFAULT_PROFILE["col_price"], DEFAULT_PROFILE["col_qty"], DEFAULT_PROFILE["col_code"]))
    return row


async def _profile_for(supplier_id: int) -> dict:
    p = await db.fetchone(
        "SELECT * FROM supplier_price_profiles WHERE supplier_id=%s ORDER BY id LIMIT 1",
        (supplier_id,))
    return p or DEFAULT_PROFILE


@router.post("/prices/upload")
async def upload_price(supplier_id: int = Query(...), file: UploadFile = File(...)):
    """Ручная загрузка прайса XLSX (резервный путь; основной — приём с почты)."""
    sup = await db.fetchone("SELECT id FROM suppliers WHERE id=%s", (supplier_id,))
    if not sup:
        raise HTTPException(404, "supplier not found")
    data = await file.read()
    profile = await _profile_for(supplier_id)
    try:
        rows = prices.parse(data, file.filename or "", profile)
    except Exception as e:
        raise HTTPException(400, f"parse error: {e}")
    result = await prices.ingest(supplier_id, rows)
    await prices.backfill_brands()       # производитель из maker прайса
    await prices.backfill_dimensions()   # вес/объём для расчёта доставки
    try:
        await search.reindex()   # чтобы поиск сразу видел новые цены/наличие/производителя
    except Exception as e:
        print("reindex after price ingest failed:", e)
    return {"supplier_id": supplier_id, "file": file.filename, **result}


@router.get("/prices/unmatched")
async def unmatched(limit: int = 100):
    """Очередь модерации: нечёткие и несопоставленные позиции прайса."""
    return await db.fetch(
        """SELECT sp.id, sp.supplier_id, sp.article, sp.name, sp.price, sp.qty,
                  sp.match_method, sp.match_confidence, sp.matched_product_id,
                  p.manufacturer_article AS candidate_article, p.name AS candidate_name
           FROM supplier_prices sp
           LEFT JOIN products p ON p.id = sp.matched_product_id
           WHERE sp.match_method IS NULL OR sp.match_method='fuzzy'
           ORDER BY sp.received_at DESC, sp.match_confidence DESC NULLS LAST
           LIMIT %s""", (limit,))


@router.post("/prices/match")
async def confirm_match(supplier_price_id: int, product_id: int):
    """Ручное подтверждение сопоставления позиции прайса товару."""
    await db.fetchone(
        """UPDATE supplier_prices SET matched_product_id=%s,
           match_method='manual', match_confidence=1.0 WHERE id=%s RETURNING id""",
        (product_id, supplier_price_id))
    return {"ok": True}
