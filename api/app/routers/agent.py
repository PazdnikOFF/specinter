"""Аутентифицированный шлюз для внешнего ИИ-агента (заголовок X-API-Key).

Отдаёт РОВНО нужные агенту инструменты, переиспользуя логику витрины (без дублей):
поиск → подбор по артикулу → карточка/срок → расчёт доставки → заявка/заказ.
Все маршруты под require_agent_key (см. agent_auth). Витринный /api не затрагивается.
"""
from fastapi import APIRouter, Body, Depends, Query

from ..agent_auth import require_agent_key
from . import catalog as _catalog
from .integrations import delivery_estimate as _delivery_estimate
from .orders import OrderIn, create_order as _create_order
from .quotes import QuoteIn, create_quote as _create_quote

router = APIRouter(prefix="/agent", tags=["agent (X-API-Key)"],
                   dependencies=[Depends(require_agent_key)])


@router.get("/whoami")
async def whoami():
    """Проверка ключа: 200 = ключ валиден."""
    return {"ok": True, "scope": "agent"}


@router.get("/search")
async def agent_search(q: str = Query(..., min_length=1, description="артикул/аналог/название"),
                       limit: int = Query(20, le=100), offset: int = 0,
                       in_stock: bool | None = None):
    """Поиск по каталогу (артикул/аналог/название)."""
    return await _catalog.search_catalog(q=q, limit=limit, offset=offset, in_stock=in_stock)


@router.get("/products/by-article/{article}")
async def agent_by_article(article: str):
    """Точный подбор по артикулу производителя + через аналоги."""
    return await _catalog.get_by_article(article)


@router.get("/products/{product_id}")
async def agent_product(product_id: int):
    """Карточка товара: цены/наличие/срок, изображения, аналоги (без поставщика)."""
    return await _catalog.get_product(product_id)


@router.get("/products/{product_id}/eta")
async def agent_eta(product_id: int, city: str | None = Query(None, description="город клиента")):
    """Срок доставки товара до города клиента."""
    return await _catalog.product_eta(product_id, city)


# --- навигация по каталогу (дерево категорий/узлов) ------------------------

@router.get("/catalog/roots")
async def agent_catalog_roots():
    """Верх витрины: модели техники / двигатели / каталоги производителей."""
    return await _catalog.catalog_roots()


@router.get("/catalog/browse")
async def agent_catalog_browse(
    category: int = Query(..., description="id категории"),
    sort: str = Query("default"), stock: bool = Query(False),
    q: str | None = Query(None), page: int = Query(1, ge=1),
    per_page: int = Query(24, ge=1, le=96)):
    """Товары узла (по поддереву) + подкатегории + хлебные крошки."""
    return await _catalog.catalog_browse(category=category, sort=sort, stock=stock,
                                         q=q, page=page, per_page=per_page)


@router.get("/categories")
async def agent_categories(parent_id: int | None = None):
    """Дети категории (или корни, если parent_id не задан)."""
    return await _catalog.list_categories(parent_id)


@router.post("/delivery/estimate")
async def agent_delivery(body: dict = Body(...)):
    """Срок и стоимость доставки по составу корзины (город + позиции)."""
    return await _delivery_estimate(body)


@router.post("/quotes")
async def agent_quote(payload: QuoteIn):
    """Заявка на подбор / запрос цены (RFQ). auto_process=true → сразу подбор."""
    return await _create_quote(payload)


@router.post("/orders")
async def agent_order(payload: OrderIn):
    """Оформление заказа (клиент + позиции; при заданной 1С:УНФ — создание документов)."""
    return await _create_order(payload)
