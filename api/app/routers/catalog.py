from fastapi import APIRouter, Query, HTTPException
from .. import db, search, logistics
from ..config import CATALOG_ROOT_ID

router = APIRouter(prefix="/api", tags=["catalog"])

# Разрешённые сортировки витрины (защита от инъекций в ORDER BY).
_SORTS = {
    # По умолчанию — по ПОЗИЦИИ на схеме (узлы каталога), а без позиций — по алфавиту.
    "default":    "(position_num IS NULL), position_num ASC, name ASC",
    "price_asc":  "(min_price IS NULL), min_price ASC, name",
    "price_desc": "min_price DESC NULLS LAST, name",
    "stock":      "in_stock DESC NULLS LAST, (min_price IS NULL), min_price ASC",
    "name":       "name ASC",
}

# Товары, привязанные НЕПОСРЕДСТВЕННО к категории (для drill-down по узлам).
# Дерево навигируется группами-плитками, а карточки показываются на своём уровне.
# Поиск ВНУТРИ группы: товары всего поддерева, отфильтрованные по артикулу/названию.
_SUBTREE_SEARCH = """
  WITH agg AS (
    SELECT p.id, p.manufacturer_article, p.name, p.brand, p.primary_image,
           NULL::text AS position, NULL::int AS position_num,
           MIN(o.price) FILTER (WHERE o.price IS NOT NULL) AS min_price,
           BOOL_OR(o.in_stock) AS in_stock,
           (SELECT MIN(s.delivery_days) FROM offers o2 JOIN suppliers s ON s.id=o2.supplier_id
              WHERE o2.product_id=p.id AND o2.source='price') AS eta_days
    FROM categories d
    JOIN product_categories pc ON pc.category_id = d.id
    JOIN products p ON p.id = pc.product_id
    LEFT JOIN offers o ON o.product_id = p.id
    WHERE (d.path = %(path)s OR d.path LIKE %(path)s || '/%%')
      AND p.visible
      AND (p.manufacturer_article ILIKE %(q)s OR p.name ILIKE %(q)s OR p.brand ILIKE %(q)s)
    GROUP BY p.id
  )
"""

_DIRECT_AGG = """
  WITH agg AS (
    SELECT p.id, p.manufacturer_article, p.name, p.brand, p.primary_image,
           MAX(pc.position) AS position,
           MAX(nullif(regexp_replace(coalesce(pc.position,''), '[^0-9]', '', 'g'), '')::int) AS position_num,
           MIN(o.price) FILTER (WHERE o.price IS NOT NULL) AS min_price,
           BOOL_OR(o.in_stock) AS in_stock,
           (SELECT MIN(s.delivery_days) FROM offers o2 JOIN suppliers s ON s.id=o2.supplier_id
              WHERE o2.product_id=p.id AND o2.source='price') AS eta_days
    FROM product_categories pc
    JOIN products p ON p.id = pc.product_id
    LEFT JOIN offers o ON o.product_id = p.id
    WHERE pc.category_id = %(cat)s AND p.visible
    GROUP BY p.id
  )
"""


@router.get("/search")
async def search_catalog(
    q: str = Query(..., min_length=1, description="артикул, аналог или название"),
    limit: int = Query(20, le=100),
    offset: int = 0,
    in_stock: bool | None = None,
):
    """Мгновенный поиск по артикулу/аналогу/названию через Meilisearch."""
    res = search.search(q, limit=limit, offset=offset, in_stock=in_stock)
    return {
        "query": q,
        "total": res.get("estimatedTotalHits"),
        "hits": res.get("hits", []),
    }


@router.get("/products/{product_id}")
async def get_product(product_id: int):
    p = await db.fetchone("SELECT * FROM products WHERE id=%s AND visible", (product_id,))
    if not p:
        raise HTTPException(404, "product not found")
    p["images"] = await db.fetch(
        "SELECT url, sort FROM product_images WHERE product_id=%s ORDER BY sort", (product_id,))
    # В каталоге НЕ раскрываем поставщика — только цену, наличие и срок поставки
    # (срок фиксируется в карточке поставщика: delivery_days / delivery_note).
    p["offers"] = await db.fetch(
        """SELECT o.id, o.article, o.price, o.qty, o.in_stock,
                  s.delivery_days, s.delivery_note
           FROM offers o LEFT JOIN suppliers s ON s.id=o.supplier_id
           WHERE o.product_id=%s ORDER BY o.price NULLS LAST""", (product_id,))
    # Аналоги + (если у аналога есть карточка) его минимальная цена, наличие и срок.
    # ИСКЛЮЧАЕМ саму карточку (self-link и совпадение артикула) и дубли (по норм. артикулу).
    p["analogs"] = await db.fetch(
        """SELECT DISTINCT ON (a.normalized_article)
                  a.analog_article, a.linked_product_id,
                  COALESCE(lp.name, a.analog_name) AS analog_name,
                  lp.brand AS brand,
                  (SELECT c.name FROM product_categories pc JOIN categories c ON c.id=pc.category_id
                     WHERE pc.product_id=a.linked_product_id
                     ORDER BY c.level DESC LIMIT 1) AS group_name,
                  (SELECT MIN(price) FROM offers o
                     WHERE o.product_id=a.linked_product_id AND o.price IS NOT NULL) AS min_price,
                  (SELECT BOOL_OR(in_stock) FROM offers o
                     WHERE o.product_id=a.linked_product_id) AS in_stock,
                  (SELECT MIN(s.delivery_days) FROM offers o JOIN suppliers s ON s.id=o.supplier_id
                     WHERE o.product_id=a.linked_product_id AND o.source='price') AS eta_days
           FROM analogs a
           LEFT JOIN products lp ON lp.id = a.linked_product_id
           WHERE a.product_id = %(pid)s
             AND a.linked_product_id IS DISTINCT FROM %(pid)s
             AND a.normalized_article IS DISTINCT FROM
                 (SELECT normalized_article FROM products WHERE id = %(pid)s)
           ORDER BY a.normalized_article, a.linked_product_id NULLS LAST""",
        {"pid": product_id})
    # Детали, стоящие на ТОЙ ЖЕ позиции схемы узла (напр. полуось левая/правая,
    # исполнения разных производителей). Это НЕ аналоги (левая≠правая), поэтому
    # отдаём отдельным блоком position_variants с ценой/наличием/сроком.
    siblings = await db.fetch(
        """SELECT DISTINCT ON (p2.id)
                  p2.manufacturer_article AS analog_article, p2.id AS linked_product_id,
                  p2.name AS analog_name, p2.brand AS brand,
                  (SELECT c.name FROM product_categories pc2 JOIN categories c ON c.id=pc2.category_id
                     WHERE pc2.product_id=p2.id ORDER BY c.level DESC LIMIT 1) AS group_name,
                  (SELECT MIN(price) FROM offers o
                     WHERE o.product_id=p2.id AND o.price IS NOT NULL) AS min_price,
                  (SELECT BOOL_OR(in_stock) FROM offers o WHERE o.product_id=p2.id) AS in_stock,
                  (SELECT MIN(s.delivery_days) FROM offers o JOIN suppliers s ON s.id=o.supplier_id
                     WHERE o.product_id=p2.id AND o.source='price') AS eta_days
           FROM product_categories pcs
           JOIN categories cc ON cc.id=pcs.category_id AND cc.path LIKE %(root)s
           JOIN product_categories pc2
                ON pc2.category_id=pcs.category_id AND pc2.position=pcs.position
               AND pc2.product_id<>pcs.product_id
           JOIN products p2 ON p2.id=pc2.product_id AND p2.visible
           WHERE pcs.product_id=%(pid)s AND coalesce(pcs.position,'') <> ''
           ORDER BY p2.id
           LIMIT 100""",
        {"pid": product_id, "root": f"%/{CATALOG_ROOT_ID}/%"})
    # Не дублируем детали, которые уже перечислены как настоящие аналоги.
    analog_ids = {a["linked_product_id"] for a in p["analogs"] if a["linked_product_id"] is not None}
    p["position_variants"] = [s for s in siblings if s["linked_product_id"] not in analog_ids]
    for a in (*p["analogs"], *p["position_variants"]):
        a["min_price"] = float(a["min_price"]) if a["min_price"] is not None else None
    cats = await db.fetch(
        """SELECT c.id, c.name, c.path, c.image, pc.position, pc.sort
           FROM categories c
           JOIN product_categories pc ON pc.category_id=c.id
           WHERE pc.product_id=%s
           ORDER BY pc.sort, c.name""", (product_id,))
    p["categories"] = cats

    # Применимость: для каждой схемы строим хлебную тропу от корня витрины
    # (модель → узел → подгруппа) + позиция детали на схеме.
    all_ids = {int(x) for c in cats for x in (c["path"] or "").strip("/").split("/") if x}
    names = {}
    if all_ids:
        for r in await db.fetch("SELECT id, name FROM categories WHERE id = ANY(%s)", (list(all_ids),)):
            names[r["id"]] = r["name"]
    applicability = []
    for c in cats:
        ids = [int(x) for x in (c["path"] or "").strip("/").split("/") if x]
        trail, seen = [], False
        for cid in ids:
            if cid == CATALOG_ROOT_ID:
                seen = True
                continue
            if seen and cid in names:
                trail.append({"id": cid, "name": names[cid]})
        applicability.append({
            "category_id": c["id"],
            "name": c["name"],
            "position": c["position"],
            "scheme_image": c["image"],     # изображение схемы узла (если есть)
            "trail": trail,
        })
    # Схемы узлов, где у детали есть позиция (для блока «Схема узла» в карточке).
    # Берём из ПОЛНОГО набора — до фильтрации применимости.
    p["schemes"] = [a for a in applicability if a["scheme_image"]]

    # Применимость для показа: убираем «пустые строки» (записи вне дерева витрины,
    # напр. свалка «ЗАПЧАСТИ» id 451 — её путь не содержит корень каталога → trail пуст)
    # и дубли (одинаковая хлебная тропа); при совпадении оставляем запись с позицией/схемой.
    clean, seen_sig = [], {}
    for a in applicability:
        if not a["trail"]:
            continue
        sig = tuple(t["id"] for t in a["trail"])
        prev = seen_sig.get(sig)
        if prev is None:
            seen_sig[sig] = len(clean)
            clean.append(a)
        elif (a["position"] or a["scheme_image"]) and not (clean[prev]["position"] or clean[prev]["scheme_image"]):
            clean[prev] = a       # предпочитаем более информативную запись
    p["applicability"] = clean
    return p


@router.get("/products/by-article/{article}")
async def get_by_article(article: str):
    """Точный подбор по артикулу производителя (нормализованный) + через аналоги."""
    rows = await db.fetch(
        """SELECT id, manufacturer_article, name, brand, slug, primary_image, 'exact' AS match
           FROM products WHERE normalized_article = norm_article(%s) AND visible
           UNION
           SELECT p.id, p.manufacturer_article, p.name, p.brand, p.slug, p.primary_image, 'analog'
           FROM products p JOIN analogs a ON a.product_id=p.id
           WHERE a.normalized_article = norm_article(%s) AND p.visible""",
        (article, article))
    return {"article": article, "results": rows}


@router.get("/products/{product_id}/eta")
async def product_eta(product_id: int, city: str | None = Query(None, description="город клиента")):
    """Срок доставки товара: в наличии → отгрузка; под заказ → пополнение + отгрузка."""
    o = await db.fetchone(
        """SELECT o.in_stock, s.city AS supplier_city
           FROM offers o LEFT JOIN suppliers s ON s.id=o.supplier_id
           WHERE o.product_id=%s ORDER BY o.in_stock DESC, o.price ASC NULLS LAST LIMIT 1""",
        (product_id,))
    if not o:
        return {"product_id": product_id, "note": "нет предложений — срок уточняется"}
    return {"product_id": product_id,
            **logistics.estimate(bool(o["in_stock"]), o["supplier_city"], city)}


@router.get("/categories")
async def list_categories(parent_id: int | None = None):
    if parent_id is None:
        rows = await db.fetch(
            "SELECT id, name, slug, level, image FROM categories WHERE parent_id IS NULL AND visible ORDER BY sort")
    else:
        rows = await db.fetch(
            "SELECT id, name, slug, level, image FROM categories WHERE parent_id=%s AND visible ORDER BY sort",
            (parent_id,))
    return rows


# --- Витринный каталог: просмотр по маркам техники и узлам ------------------

async def _children(parent_id: int, include_hidden: bool = False):
    """Подкатегории с числом товаров в поддереве (по материализованному пути).
    В drill-down (include_hidden=True) показываем узлы с товарами даже если visible=false
    (в legacy у части узлов флаг сброшен, иначе модель выглядит пустой)."""
    return await db.fetch(
        """SELECT id, name, slug, image, product_count FROM (
             SELECT c.id, c.name, c.slug, c.image, c.sort, c.visible,
                    (SELECT count(DISTINCT pc.product_id)
                       FROM categories d JOIN product_categories pc ON pc.category_id = d.id
                       WHERE d.path = c.path OR d.path LIKE c.path || '/%%') AS product_count
             FROM categories c WHERE c.parent_id = %s
           ) t
           WHERE product_count > 0 AND (visible OR %s)
           ORDER BY product_count DESC, sort""",
        (parent_id, include_hidden))


def _classify(name: str) -> str:
    """Раскладываем корневые узлы на смысловые секции витрины."""
    n = (name or "").strip().lower()
    if n.startswith("двигатель") or n.startswith("двс"):
        return "engines"
    if n.endswith("запчасти") or "фильтр" in n or "форсунк" in n or n in ("инструмент",):
        return "brands"
    return "models"


@router.get("/catalog/roots")
async def catalog_roots():
    """Верхний уровень витрины: модели техники, двигатели и каталоги производителей."""
    rows = await _children(CATALOG_ROOT_ID)
    items = [r for r in rows if r["product_count"]]  # только узлы с товарами
    for r in items:
        r["group"] = _classify(r["name"])
    return {"root_id": CATALOG_ROOT_ID, "items": items}


@router.get("/catalog/browse")
async def catalog_browse(
    category: int = Query(..., description="id категории для просмотра"),
    sort: str = Query("default"),
    stock: bool = Query(False, description="только в наличии"),
    q: str | None = Query(None, description="поиск внутри группы (по поддереву)"),
    page: int = Query(1, ge=1),
    per_page: int = Query(24, ge=1, le=96),
):
    """Товары внутри категории (по всему поддереву) + подкатегории + хлебные крошки.
    q — поиск по артикулу/названию ВНУТРИ текущей группы (всё поддерево)."""
    # без AND visible: в legacy у части узлов флаг сброшен, но товары есть — их нужно открывать
    cat = await db.fetchone(
        "SELECT id, name, slug, path, level, image FROM categories WHERE id=%s", (category,))
    if not cat:
        raise HTTPException(404, "category not found")

    order = _SORTS.get(sort, _SORTS["default"])
    having = "WHERE in_stock" if stock else ""
    params = {"cat": category, "limit": per_page, "offset": (page - 1) * per_page}
    q = (q or "").strip()
    if q:
        agg, params["path"], params["q"] = _SUBTREE_SEARCH, cat["path"], f"%{q}%"
    else:
        agg = _DIRECT_AGG

    total_row = await db.fetchone(
        f"{agg} SELECT count(*) AS n FROM agg {having}", params)
    products = await db.fetch(
        f"{agg} SELECT * FROM agg {having} ORDER BY {order} LIMIT %(limit)s OFFSET %(offset)s",
        params)

    # хлебные крошки из материализованного пути, начиная от корня витрины
    path_ids = [int(x) for x in cat["path"].strip("/").split("/") if x]
    crumbs = []
    if path_ids:
        rows = await db.fetch(
            "SELECT id, name FROM categories WHERE id = ANY(%s)", (path_ids,))
        by_id = {r["id"]: r["name"] for r in rows}
        seen = False
        for cid in path_ids:
            if cid == CATALOG_ROOT_ID:
                seen = True
            if seen and cid in by_id:
                crumbs.append({"id": cid, "name": by_id[cid]})

    for p in products:
        p["min_price"] = float(p["min_price"]) if p["min_price"] is not None else None

    return {
        "category": {"id": cat["id"], "name": cat["name"], "path": cat["path"], "image": cat["image"]},
        "breadcrumbs": crumbs,
        "children": await _children(category, include_hidden=True),
        "total": total_row["n"],
        "page": page,
        "per_page": per_page,
        "products": products,
    }
