"""Админка: авторизация + табличные разделы (мой прайс, сырьё прайсов, правка
каталога и поставщиков) + управление фото товаров. Данные под require_admin."""
import ipaddress
import os
import re
import socket
import uuid
from urllib.parse import urlparse

import httpx
from fastapi import APIRouter, Body, Depends, File, HTTPException, Query, Response, UploadFile

from .. import assistant, auth, db, prices, search, settings as app_settings

MEDIA_DIR = os.environ.get("MEDIA_DIR", "/media")
ALLOWED_IMG = {".jpg", ".jpeg", ".png", ".webp", ".gif"}
CTYPE_EXT = {"image/jpeg": ".jpg", "image/png": ".png", "image/webp": ".webp", "image/gif": ".gif"}
MAX_IMG_BYTES = 15 * 1024 * 1024


def _safe_public_url(url: str):
    """Защита от SSRF: только http/https и публичный IP (не localhost/приватные сети)."""
    p = urlparse(url)
    if p.scheme not in ("http", "https") or not p.hostname:
        raise HTTPException(400, "нужен http(s) URL")
    try:
        infos = socket.getaddrinfo(p.hostname, None)
    except OSError:
        raise HTTPException(400, "хост не резолвится")
    for info in infos:
        ip = ipaddress.ip_address(info[4][0])
        if ip.is_private or ip.is_loopback or ip.is_link_local or ip.is_reserved:
            raise HTTPException(400, "недопустимый (внутренний) адрес")

# Открытый роутер: вход/выход/проверка сессии.
auth_router = APIRouter(prefix="/api/admin", tags=["admin: auth"])


@auth_router.post("/login")
async def login(response: Response, body: dict = Body(...)):
    if not auth.login(body.get("username", ""), body.get("password", ""), response):
        raise HTTPException(401, "Неверный логин или пароль")
    return {"ok": True}


@auth_router.post("/logout")
async def logout(response: Response):
    auth.logout(response)
    return {"ok": True}


@auth_router.get("/me")
async def me(_: bool = Depends(auth.require_admin)):
    return {"ok": True, "user": auth.ADMIN_USER}


# Защищённый роутер: всё остальное требует сессии.
router = APIRouter(prefix="/api/admin", tags=["admin"],
                   dependencies=[Depends(auth.require_admin)])


@router.get("/my-price")
async def my_price(q: str | None = None, only_priced: bool = False,
                   page: int = Query(1, ge=1), per_page: int = Query(50, ge=1, le=200)):
    """Итоговый прайс: карточки с продажной ценой (мин. по поставщикам), наличием,
    числом поставщиков-источников. Поиск по артикулу/названию, пагинация."""
    where = ["p.visible IS NOT NULL"]
    params: dict = {"limit": per_page, "offset": (page - 1) * per_page}
    if q:
        where.append("(p.manufacturer_article ILIKE %(q)s OR p.name ILIKE %(q)s)")
        params["q"] = f"%{q}%"
    having = "HAVING count(o.id) FILTER (WHERE o.source='price') > 0" if only_priced else ""
    wsql = " AND ".join(where)
    base = f"""
        FROM products p
        LEFT JOIN offers o ON o.product_id = p.id
        WHERE {wsql}
        GROUP BY p.id {having}"""
    total = await db.fetchone(f"SELECT count(*) AS n FROM (SELECT p.id {base}) t", params)
    rows = await db.fetch(f"""
        SELECT p.id, p.manufacturer_article, p.name, p.visible,
               MIN(o.price) FILTER (WHERE o.source='price' AND o.price IS NOT NULL) AS sell_price,
               count(DISTINCT o.supplier_id) FILTER (WHERE o.source='price') AS suppliers,
               BOOL_OR(o.in_stock) FILTER (WHERE o.source='price') AS in_stock
        {base}
        ORDER BY p.manufacturer_article
        LIMIT %(limit)s OFFSET %(offset)s""", params)
    for r in rows:
        r["sell_price"] = float(r["sell_price"]) if r["sell_price"] is not None else None
    return {"total": total["n"], "page": page, "per_page": per_page, "items": rows}


@router.patch("/products/{product_id}")
async def edit_product(product_id: int, body: dict = Body(...)):
    """Правка карточки каталога: название и видимость."""
    fields, params = [], []
    if "name" in body:
        fields.append("name=%s"); params.append(body["name"])
    if "visible" in body:
        fields.append("visible=%s"); params.append(bool(body["visible"]))
    if not fields:
        raise HTTPException(400, "нет полей для обновления")
    params.append(product_id)
    row = await db.fetchone(
        f"UPDATE products SET {', '.join(fields)}, updated_at=now() WHERE id=%s RETURNING id",
        tuple(params))
    if not row:
        raise HTTPException(404, "товар не найден")
    return {"ok": True}


@router.get("/supplier-prices")
async def supplier_prices(supplier_id: int | None = None, q: str | None = None,
                          matched: str | None = Query(None, description="matched|unmatched"),
                          page: int = Query(1, ge=1), per_page: int = Query(50, ge=1, le=200)):
    """Сырьё прайсов построчно: по поставщику, статусу матчинга, с поиском."""
    where, params = ["1=1"], {"limit": per_page, "offset": (page - 1) * per_page}
    if supplier_id:
        where.append("sp.supplier_id=%(sid)s"); params["sid"] = supplier_id
    if q:
        where.append("(sp.article ILIKE %(q)s OR sp.name ILIKE %(q)s)"); params["q"] = f"%{q}%"
    if matched == "matched":
        where.append("sp.matched_product_id IS NOT NULL")
    elif matched == "unmatched":
        where.append("sp.matched_product_id IS NULL")
    wsql = " AND ".join(where)
    total = await db.fetchone(
        f"SELECT count(*) AS n FROM supplier_prices sp WHERE {wsql}", params)
    rows = await db.fetch(f"""
        SELECT sp.id, sp.supplier_id, s.name AS supplier, sp.article, sp.name, sp.maker,
               sp.price, sp.qty, sp.match_method, sp.match_confidence, sp.matched_product_id,
               sp.received_at
        FROM supplier_prices sp JOIN suppliers s ON s.id=sp.supplier_id
        WHERE {wsql}
        ORDER BY sp.received_at DESC, sp.id DESC
        LIMIT %(limit)s OFFSET %(offset)s""", params)
    for r in rows:
        r["price"] = float(r["price"]) if r["price"] is not None else None
        r["qty"] = float(r["qty"]) if r["qty"] is not None else None
        r["match_confidence"] = float(r["match_confidence"]) if r["match_confidence"] is not None else None
    return {"total": total["n"], "page": page, "per_page": per_page, "items": rows}


@router.patch("/suppliers/{supplier_id}")
async def edit_supplier(supplier_id: int, body: dict = Body(...)):
    """Правка карточки поставщика: имя, e-mail отправителя, город, СРОК ПОСТАВКИ, наценка."""
    allowed = {"name", "sender_email", "city", "delivery_days", "delivery_note",
               "markup_percent", "active"}
    fields, params = [], []
    for k, v in body.items():
        if k in allowed:
            fields.append(f"{k}=%s"); params.append(v)
    if not fields:
        raise HTTPException(400, "нет полей для обновления")
    params.append(supplier_id)
    row = await db.fetchone(
        f"UPDATE suppliers SET {', '.join(fields)} WHERE id=%s RETURNING id", tuple(params))
    if not row:
        raise HTTPException(404, "поставщик не найден")
    # Наценка/округление изменились → автопересчёт розничных цен этого поставщика.
    recomputed = None
    if "markup_percent" in body or "price_round" in body:
        recomputed = await prices.recompute_offers(supplier_id)
        try:
            await search.reindex()   # чтобы витрина/поиск сразу увидели новые цены
        except Exception as e:
            print("reindex after markup change failed:", e)
    return {"ok": True, "offers_recomputed": recomputed}


@router.post("/reindex-catalog")
async def reindex_catalog():
    n = await search.reindex()
    return {"indexed": n}


# --- Настройки интеграций (токены задаются здесь, без правки .env) ---
@router.get("/settings")
async def get_settings():
    return {"settings": app_settings.public_status()}


@router.put("/settings")
async def put_setting(body: dict = Body(...)):
    key = body.get("key")
    if key not in app_settings.KNOWN:
        raise HTTPException(400, "неизвестный ключ настройки")
    await app_settings.set_value(key, (body.get("value") or "").strip() or None)
    return {"ok": True, "settings": app_settings.public_status()}


@router.post("/assistant")
async def admin_assistant(body: dict = Body(...)):
    """ИИ-администратор портала (полный доступ к управлению через инструменты)."""
    return await assistant.chat("admin", body.get("messages") or [])


# ---------------------------------------------------------------------------
# Фото товаров: ручная загрузка/управление (легальный путь — свои/лицензионные фото).
@router.get("/products/{product_id}/images")
async def product_images(product_id: int):
    p = await db.fetchone(
        "SELECT id, manufacturer_article, name, primary_image FROM products WHERE id=%s", (product_id,))
    if not p:
        raise HTTPException(404, "товар не найден")
    p["gallery"] = await db.fetch(
        "SELECT url, sort FROM product_images WHERE product_id=%s ORDER BY sort", (product_id,))
    return p


@router.post("/products/{product_id}/images")
async def upload_product_image(product_id: int, primary: bool = Query(False),
                               file: UploadFile = File(...)):
    """Загрузка фото товара в медиа-том. primary=true — сделать главным, иначе в галерею."""
    prod = await db.fetchone("SELECT id FROM products WHERE id=%s", (product_id,))
    if not prod:
        raise HTTPException(404, "товар не найден")
    ext = os.path.splitext(file.filename or "")[1].lower()
    if ext not in ALLOWED_IMG:
        raise HTTPException(400, f"недопустимый тип файла: {ext}")
    name = f"up_{uuid.uuid4().hex}{ext}"
    os.makedirs(MEDIA_DIR, exist_ok=True)
    data = await file.read()
    with open(os.path.join(MEDIA_DIR, name), "wb") as f:
        f.write(data)
    if primary:
        await db.fetchone("UPDATE products SET primary_image=%s WHERE id=%s RETURNING id", (name, product_id))
    else:
        await db.fetchone(
            """INSERT INTO product_images (product_id, url, sort)
               VALUES (%s,%s,(SELECT COALESCE(max(sort),100)+1 FROM product_images WHERE product_id=%s))
               RETURNING id""", (product_id, name, product_id))
    return {"ok": True, "url": name, "primary": primary}


@router.post("/products/{product_id}/image-from-url")
async def image_from_url(product_id: int, body: dict = Body(...)):
    """Скачать изображение по прямой ссылке и прикрепить к товару. Источник выбирает
    администратор (визуально проверяет отсутствие водяного знака в превью)."""
    prod = await db.fetchone("SELECT id FROM products WHERE id=%s", (product_id,))
    if not prod:
        raise HTTPException(404, "товар не найден")
    url = (body.get("url") or "").strip()
    primary = bool(body.get("primary"))
    _safe_public_url(url)
    try:
        ua = ("Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 "
              "(KHTML, like Gecko) Chrome/120.0 Safari/537.36")
        async with httpx.AsyncClient(timeout=25, follow_redirects=True) as c:
            r = await c.get(url, headers={"User-Agent": ua, "Referer": f"{urlparse(url).scheme}://{urlparse(url).hostname}/"})
    except httpx.HTTPError as e:
        raise HTTPException(400, f"не удалось скачать: {e}")
    if r.status_code != 200:
        raise HTTPException(400, f"источник вернул {r.status_code}")
    ctype = r.headers.get("content-type", "").split(";")[0].strip().lower()
    if not ctype.startswith("image/"):
        raise HTTPException(400, "по ссылке не изображение")
    if len(r.content) > MAX_IMG_BYTES:
        raise HTTPException(400, "файл слишком большой")
    ext = CTYPE_EXT.get(ctype) or os.path.splitext(urlparse(url).path)[1].lower()
    if ext not in ALLOWED_IMG:
        raise HTTPException(400, f"недопустимый тип: {ctype}")
    name = f"url_{uuid.uuid4().hex}{ext}"
    os.makedirs(MEDIA_DIR, exist_ok=True)
    with open(os.path.join(MEDIA_DIR, name), "wb") as f:
        f.write(r.content)
    if primary:
        await db.fetchone("UPDATE products SET primary_image=%s WHERE id=%s RETURNING id", (name, product_id))
    else:
        await db.fetchone(
            """INSERT INTO product_images (product_id, url, sort)
               VALUES (%s,%s,(SELECT COALESCE(max(sort),100)+1 FROM product_images WHERE product_id=%s))
               RETURNING id""", (product_id, name, product_id))
    return {"ok": True, "url": name, "primary": primary}


@router.post("/products/{product_id}/set-primary")
async def set_primary_image(product_id: int, body: dict = Body(...)):
    """Сделать указанное фото главным (старое главное уходит в галерею)."""
    url = body.get("url")
    if not url:
        raise HTTPException(400, "url обязателен")
    old = await db.fetchone("SELECT primary_image FROM products WHERE id=%s", (product_id,))
    if not old:
        raise HTTPException(404, "товар не найден")
    await db.fetchone("UPDATE products SET primary_image=%s WHERE id=%s RETURNING id", (url, product_id))
    await db.fetchone("DELETE FROM product_images WHERE product_id=%s AND url=%s RETURNING id", (product_id, url))
    if old["primary_image"] and old["primary_image"] != url:
        await db.fetchone(
            """INSERT INTO product_images (product_id, url, sort) VALUES (%s,%s,0)
               ON CONFLICT DO NOTHING RETURNING id""", (product_id, old["primary_image"]))
    return {"ok": True}


@router.delete("/products/{product_id}/images")
async def delete_product_image(product_id: int, url: str = Query(...)):
    """Удалить фото: из галереи или снять как главное."""
    p = await db.fetchone("SELECT primary_image FROM products WHERE id=%s", (product_id,))
    if not p:
        raise HTTPException(404, "товар не найден")
    if p["primary_image"] == url:
        await db.fetchone("UPDATE products SET primary_image=NULL WHERE id=%s RETURNING id", (product_id,))
    await db.fetchone("DELETE FROM product_images WHERE product_id=%s AND url=%s RETURNING id", (product_id, url))
    return {"ok": True}
