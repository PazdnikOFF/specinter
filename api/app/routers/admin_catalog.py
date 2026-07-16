"""Массовое управление каталогом (для бота/агента и админки): загрузка товаров,
кроссов (аналогов), каталога из файла, удаление товара. Под require_admin
(cookie ИЛИ X-Admin-Key). Матчинг/дедуп — по нормализованному артикулу (norm_article)."""
from fastapi import APIRouter, Body, File, HTTPException, Query, UploadFile

from .. import db, prices, search

router = APIRouter(prefix="/api/admin", tags=["admin: catalog bulk"])


def _clean_article(a: str, strip_suffixes=()) -> str:
    """Убирает брендовый суффикс (напр. -ATQ/-AT/-Q у линии AVTO-TECH), см. docs/03."""
    a = (a or "").strip()
    for suf in strip_suffixes:
        if suf and a.upper().endswith(suf.upper()):
            return a[: -len(suf)].rstrip(" -")
    return a


async def _reindex():
    try:
        await search.reindex()
    except Exception as e:  # meili мог не подняться — не валим загрузку
        print("reindex failed:", e)


async def _insert_products(items: list[dict], skip_existing: bool = True) -> dict:
    """Создаёт товары, дедуп по norm-артикулу (в БД и внутри партии). Без reindex."""
    created, skipped, ids = 0, 0, []
    seen: set[str] = set()
    async with db.pool.connection() as conn:
        async with conn.cursor() as cur:
            for it in items:
                art = (it.get("manufacturer_article") or it.get("article") or "").strip()
                if not art:
                    skipped += 1
                    continue
                await cur.execute("SELECT norm_article(%s) AS n", (art,))
                norm = (await cur.fetchone())["n"]
                if norm and norm in seen:
                    skipped += 1
                    continue
                if norm:
                    seen.add(norm)
                if skip_existing and norm:
                    await cur.execute(
                        "SELECT 1 FROM products WHERE normalized_article=%s LIMIT 1", (norm,))
                    if await cur.fetchone():
                        skipped += 1
                        continue
                await cur.execute(
                    """INSERT INTO products (manufacturer_article, name, brand, visible,
                                             weight_kg, volume_m3, primary_image, slug)
                       VALUES (%s,%s,%s,%s,%s,%s,%s,%s) RETURNING id""",
                    (art, it.get("name"), it.get("brand"), bool(it.get("visible", True)),
                     it.get("weight_kg"), it.get("volume_m3"),
                     it.get("primary_image"), it.get("slug")))
                ids.append((await cur.fetchone())["id"])
                created += 1
    return {"created": created, "skipped": skipped, "ids": ids[:2000]}


async def _insert_crosses(items: list[dict], source: str = "api", link: bool = True) -> dict:
    """Добавляет кроссы в analogs. items: [{article|product_id, crosses:[str], source?}].
    Дедуп по (product_id, norm(cross)); linked_product_id — если есть карточка с таким арт."""
    added, skipped, no_owner = 0, 0, 0
    async with db.pool.connection() as conn:
        async with conn.cursor() as cur:
            for it in items:
                pid = it.get("product_id")
                if not pid and it.get("article"):
                    await cur.execute(
                        "SELECT id FROM products WHERE normalized_article=norm_article(%s) LIMIT 1",
                        (it["article"],))
                    row = await cur.fetchone()
                    pid = row["id"] if row else None
                if not pid:
                    no_owner += 1
                    continue
                for cx in (it.get("crosses") or []):
                    cx = (cx or "").strip()
                    if not cx:
                        continue
                    await cur.execute(
                        """SELECT 1 FROM analogs WHERE product_id=%s
                           AND normalized_article=norm_article(%s) LIMIT 1""", (pid, cx))
                    if await cur.fetchone():
                        skipped += 1
                        continue
                    linked = None
                    if link:
                        await cur.execute(
                            """SELECT id FROM products WHERE normalized_article=norm_article(%s)
                               AND id<>%s LIMIT 1""", (cx, pid))
                        r = await cur.fetchone()
                        linked = r["id"] if r else None
                    await cur.execute(
                        """INSERT INTO analogs (product_id, analog_article, linked_product_id, source)
                           VALUES (%s,%s,%s,%s)""", (pid, cx, linked, it.get("source") or source))
                    added += 1
    return {"added": added, "skipped": skipped, "no_owner": no_owner}


# --- эндпоинты --------------------------------------------------------------

@router.post("/products/bulk")
async def products_bulk(body: dict = Body(...)):
    """Массовое создание товаров. body: {items:[{manufacturer_article, name?, brand?,
    visible?, weight_kg?, volume_m3?, primary_image?, slug?}], skip_existing?:bool(=true)}."""
    items = body.get("items") or []
    if not items:
        raise HTTPException(400, "нет items")
    res = await _insert_products(items, body.get("skip_existing", True))
    if res["created"]:
        await _reindex()
    return res


@router.post("/crosses")
async def crosses_bulk(body: dict = Body(...)):
    """Массовая загрузка кроссов (аналогов). body: {items:[{article|product_id,
    crosses:[str], source?}], source?, link?:bool(=true)}."""
    items = body.get("items") or []
    if not items:
        raise HTTPException(400, "нет items")
    res = await _insert_crosses(items, body.get("source") or "api", body.get("link", True))
    if res["added"]:
        await _reindex()
    return res


@router.post("/catalog/upload")
async def catalog_upload(
    file: UploadFile = File(...),
    col_article: int = Query(0, description="индекс колонки артикула (0-based)"),
    col_name: int | None = Query(None), col_brand: int | None = Query(None),
    col_cross: int | None = Query(None, description="колонка кросс-номера (в analogs)"),
    header_rows: int = Query(0, description="сколько строк шапки пропустить"),
    strip_suffixes: str = Query("", description="брендовые суффиксы через запятую, напр. -ATQ,-AT,-Q"),
    skip_existing: bool = Query(True)):
    """Загрузка каталога из XLSX/XLS/CSV: колонки задаются индексами. Создаёт товары
    (дедуп) + кроссы в analogs. Один reindex в конце."""
    data = await file.read()
    try:
        rows = prices._read_rows(data, file.filename or "")
    except Exception as e:
        raise HTTPException(400, f"parse error: {e}")
    suf = tuple(s.strip() for s in strip_suffixes.split(",") if s.strip())
    prod_items, cross_items = [], []
    for r in rows[header_rows:]:
        if col_article >= len(r) or r[col_article] is None:
            continue
        art = _clean_article(str(r[col_article]), suf)
        if not art:
            continue
        item = {"manufacturer_article": art}
        if col_name is not None and col_name < len(r):
            item["name"] = prices._s(r[col_name])
        if col_brand is not None and col_brand < len(r):
            item["brand"] = prices._s(r[col_brand])
        prod_items.append(item)
        if col_cross is not None and col_cross < len(r):
            cx = prices._s(r[col_cross])
            if cx:
                cross_items.append({"article": art, "crosses": [cx]})
    p_res = await _insert_products(prod_items, skip_existing)
    c_res = await _insert_crosses(cross_items, "catalog-upload") if cross_items else {"added": 0, "skipped": 0, "no_owner": 0}
    if p_res["created"] or c_res["added"]:
        await _reindex()
    return {"file": file.filename, "rows_total": len(rows), "products": p_res, "crosses": c_res}


@router.get("/export/products")
async def export_products(
    page: int = Query(1, ge=1), per_page: int = Query(200, ge=1, le=1000),
    updated_since: str | None = Query(None, description="ISO-время для инкрементальной выгрузки"),
    visible_only: bool = Query(False)):
    """Полная пагинированная выгрузка каталога для синка боту: артикул, название,
    бренд, фото, вес/объём, мин.цена, наличие, КРОССЫ (аналоги), id категорий, число
    предложений. updated_since — только изменённые (инкремент). Стабильный порядок по id."""
    params = {"limit": per_page, "offset": (page - 1) * per_page, "since": updated_since}
    where = ["(%(since)s::timestamptz IS NULL OR p.updated_at >= %(since)s::timestamptz)"]
    if visible_only:
        where.append("p.visible")
    wsql = " AND ".join(where)
    total = (await db.fetchone(f"SELECT count(*) AS n FROM products p WHERE {wsql}", params))["n"]
    rows = await db.fetch(f"""
        SELECT p.id, p.manufacturer_article, p.name, p.brand, p.slug, p.primary_image,
               p.visible, p.weight_kg, p.volume_m3, p.updated_at,
               MIN(o.price) FILTER (WHERE o.price IS NOT NULL) AS min_price,
               BOOL_OR(o.in_stock) AS in_stock,
               count(o.id) AS offers_count,
               COALESCE(array_agg(DISTINCT a.analog_article)
                        FILTER (WHERE a.analog_article IS NOT NULL), '{{}}') AS crosses,
               COALESCE(array_agg(DISTINCT pc.category_id)
                        FILTER (WHERE pc.category_id IS NOT NULL), '{{}}') AS category_ids
        FROM products p
        LEFT JOIN offers o ON o.product_id = p.id
        LEFT JOIN analogs a ON a.product_id = p.id
        LEFT JOIN product_categories pc ON pc.product_id = p.id
        WHERE {wsql}
        GROUP BY p.id
        ORDER BY p.id
        LIMIT %(limit)s OFFSET %(offset)s""", params)
    for r in rows:
        r["min_price"] = float(r["min_price"]) if r["min_price"] is not None else None
        r["weight_kg"] = float(r["weight_kg"]) if r["weight_kg"] is not None else None
        r["volume_m3"] = float(r["volume_m3"]) if r["volume_m3"] is not None else None
    return {"total": total, "page": page, "per_page": per_page,
            "pages": (total + per_page - 1) // per_page, "items": rows}


@router.get("/export/categories")
async def export_categories(visible_only: bool = Query(False)):
    """Плоский дамп всего дерева категорий (id, parent_id, path, level) — для построения
    навигации/каталогов на стороне бота. path = материализованный путь /40/65/…"""
    where = "WHERE visible" if visible_only else ""
    return await db.fetch(f"""
        SELECT id, parent_id, name, slug, path, level, image, sort, visible
        FROM categories {where} ORDER BY path, sort""")


@router.delete("/products/{product_id}")
async def delete_product(product_id: int):
    """Удаляет товар. Каскадно уходят фото/категории/аналоги/offers; в заказах ссылка
    обнуляется (позиция заказа сохраняет снимок артикула/цены)."""
    row = await db.fetchone("DELETE FROM products WHERE id=%s RETURNING id", (product_id,))
    if not row:
        raise HTTPException(404, "товар не найден")
    await _reindex()
    return {"ok": True, "deleted": product_id}
