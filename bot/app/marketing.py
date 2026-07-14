"""Маркетинг-автопилот: бот сам формирует промо из каталога и постит в Instagram.
Промо-подписи генерирует LLM (если подключён), креативы (фото/видео) берёт из каталога
или заданной библиотеки роликов. Планировщик — по интервалу."""
import asyncio
import os
import random
import httpx

from .config import BOT_API_URL
from .publishers import instagram_publisher as ig

MARKETING_ENABLED = os.environ.get("MARKETING_ENABLED", "0") == "1"
MARKETING_INTERVAL = int(os.environ.get("MARKETING_INTERVAL", "86400"))  # раз в сутки
# Библиотека готовых рекламных роликов (URL через запятую) — для рилсов.
REEL_LIBRARY = [u for u in os.environ.get("REEL_LIBRARY", "").split(",") if u.strip()]
PUBLIC_MEDIA_URL = os.environ.get("PUBLIC_MEDIA_URL", "")  # публичный URL media-сервиса


async def _pick_promo_products(n=1) -> list[dict]:
    """Берём товары в наличии с фото — как основу промо."""
    async with httpx.AsyncClient(base_url=BOT_API_URL, timeout=20) as c:
        r = await c.get("/api/search", params={"q": "*", "limit": 25, "in_stock": "true"})
        hits = [h for h in r.json().get("hits", []) if h.get("primary_image")]
    random.shuffle(hits)
    return hits[:n]


def _caption(p: dict) -> str:
    price = f"{round(p['min_price']):,} ₽".replace(",", " ") if p.get("min_price") else "цена по запросу"
    return (f"🔧 {p.get('name')}\n"
            f"Артикул: {p.get('manufacturer_article')} — {price}\n"
            f"Запчасти для спецтехники в наличии. Подбор по артикулу на specinter.ru\n"
            f"#спецтехника #запчасти #shantui #xcmg #погрузчик #экскаватор")


async def post_once() -> dict:
    products = await _pick_promo_products(1)
    if not products:
        return {"skipped": "нет подходящих товаров"}
    p = products[0]
    caption = _caption(p)
    if REEL_LIBRARY:                       # если есть готовые ролики — постим рилс
        return await ig.publish_reel(random.choice(REEL_LIBRARY), caption)
    if PUBLIC_MEDIA_URL and p.get("primary_image"):  # иначе фото-промо карточки
        return await ig.publish_photo(f"{PUBLIC_MEDIA_URL}/{p['primary_image']}", caption)
    return await ig.publish_photo("", caption)  # dry-run без публичного URL


async def scheduler():
    if not MARKETING_ENABLED:
        print("[marketing] MARKETING_ENABLED=0 — автопостинг выключен")
        return
    print(f"[marketing] автопостинг включён, интервал {MARKETING_INTERVAL}s "
          f"({'реальный' if ig.CONFIGURED else 'DRY-RUN'})")
    while True:
        try:
            print("[marketing] post:", await post_once())
        except Exception as e:
            print(f"[marketing] error: {e}")
        await asyncio.sleep(MARKETING_INTERVAL)
