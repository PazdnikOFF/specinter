"""Автопостинг в Instagram через Graph API Content Publishing.
Бот сам публикует РИЛСЫ и фото-промо. Реклама (платное продвижение) — Marketing API
(нужен рекламный кабинет и бюджет), см. promote_ad().

Требуется бизнес/creator-аккаунт Instagram, привязанный к Facebook-странице, и
долгоживущий токен: IG_USER_ID, IG_ACCESS_TOKEN. Без них — DRY-RUN (логирует, не постит)."""
import asyncio
import os
import httpx

IG_USER_ID = os.environ.get("IG_USER_ID", "")
IG_ACCESS_TOKEN = os.environ.get("IG_ACCESS_TOKEN", "")
IG_AD_ACCOUNT_ID = os.environ.get("IG_AD_ACCOUNT_ID", "")
GRAPH = os.environ.get("IG_GRAPH_URL", "https://graph.facebook.com/v21.0")

CONFIGURED = bool(IG_USER_ID and IG_ACCESS_TOKEN)


async def _wait_container(client, container_id: str, tries: int = 20):
    """Ждём готовности медиа-контейнера (видео обрабатывается асинхронно)."""
    for _ in range(tries):
        r = await client.get(f"{GRAPH}/{container_id}",
                             params={"fields": "status_code", "access_token": IG_ACCESS_TOKEN})
        if r.json().get("status_code") == "FINISHED":
            return True
        await asyncio.sleep(5)
    return False


async def publish_reel(video_url: str, caption: str) -> dict:
    """Опубликовать РИЛС из видео по URL с подписью."""
    if not CONFIGURED:
        print(f"[ig] DRY-RUN reel: {caption[:60]} | {video_url}")
        return {"dry_run": True, "type": "reel", "caption": caption}
    async with httpx.AsyncClient(timeout=60) as client:
        r = await client.post(f"{GRAPH}/{IG_USER_ID}/media", params={
            "media_type": "REELS", "video_url": video_url,
            "caption": caption, "access_token": IG_ACCESS_TOKEN})
        cid = r.json().get("id")
        if not cid or not await _wait_container(client, cid):
            return {"error": "container not ready", "resp": r.json()}
        pub = await client.post(f"{GRAPH}/{IG_USER_ID}/media_publish",
                                params={"creation_id": cid, "access_token": IG_ACCESS_TOKEN})
        return {"published": pub.json()}


async def publish_photo(image_url: str, caption: str) -> dict:
    """Опубликовать фото-промо (например, карточку товара)."""
    if not CONFIGURED:
        print(f"[ig] DRY-RUN photo: {caption[:60]} | {image_url}")
        return {"dry_run": True, "type": "photo", "caption": caption}
    async with httpx.AsyncClient(timeout=60) as client:
        r = await client.post(f"{GRAPH}/{IG_USER_ID}/media", params={
            "image_url": image_url, "caption": caption, "access_token": IG_ACCESS_TOKEN})
        cid = r.json().get("id")
        pub = await client.post(f"{GRAPH}/{IG_USER_ID}/media_publish",
                                params={"creation_id": cid, "access_token": IG_ACCESS_TOKEN})
        return {"published": pub.json()}


async def promote_ad(post_id: str, daily_budget_rub: int) -> dict:
    """Платное продвижение поста (реклама). Marketing API — нужен рекламный кабинет и бюджет.
    Заготовка: реальное создание кампании включается при наличии IG_AD_ACCOUNT_ID и прав ads_management."""
    if not (CONFIGURED and IG_AD_ACCOUNT_ID):
        print(f"[ig] DRY-RUN ad: post={post_id}, budget={daily_budget_rub}₽/день")
        return {"dry_run": True, "note": "нужен рекламный кабинет (IG_AD_ACCOUNT_ID) и ads_management"}
    # TODO: Marketing API — создать campaign/adset/ad с целью трафик/сообщения.
    return {"todo": "marketing_api", "post_id": post_id}
