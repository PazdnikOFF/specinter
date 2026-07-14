"""Публикация видео/Shorts на YouTube. Env-gated: без YOUTUBE_ACCESS_TOKEN — DRY-RUN
(логирует, не постит). Загрузка на YouTube требует OAuth2-токена (scope youtube.upload)."""
import os
import httpx

YOUTUBE_ACCESS_TOKEN = os.environ.get("YOUTUBE_ACCESS_TOKEN", "") or os.environ.get("YOUTUBE_API_KEY", "")
YOUTUBE_CHANNEL_ID = os.environ.get("YOUTUBE_CHANNEL_ID", "")
UPLOAD_URL = "https://www.googleapis.com/upload/youtube/v3/videos"

CONFIGURED = bool(YOUTUBE_ACCESS_TOKEN)


async def publish_short(video_url: str, title: str, description: str = "",
                        tags: list[str] | None = None) -> dict:
    """Загрузить ролик (Shorts) на YouTube. Требуется OAuth2 access token (youtube.upload)."""
    if not CONFIGURED:
        return {"mode": "dry-run", "platform": "youtube", "title": title,
                "note": "YOUTUBE_ACCESS_TOKEN не задан — публикация не выполнена (dry-run)"}
    async with httpx.AsyncClient(timeout=120) as c:
        # 1) скачиваем ролик
        vid = await c.get(video_url)
        if vid.status_code != 200:
            return {"error": f"не удалось скачать видео ({vid.status_code})"}
        # 2) resumable upload: метаданные + бинарь (упрощённый одношаговый вариант)
        meta = {"snippet": {"title": title, "description": description,
                            "tags": tags or [], "categoryId": "2"},
                "status": {"privacyStatus": "public", "selfDeclaredMadeForKids": False}}
        r = await c.post(
            UPLOAD_URL,
            params={"part": "snippet,status", "uploadType": "multipart"},
            headers={"Authorization": f"Bearer {YOUTUBE_ACCESS_TOKEN}"},
            files={"metadata": ("meta.json", str(meta), "application/json"),
                   "video": ("short.mp4", vid.content, "video/*")})
    if r.status_code not in (200, 201):
        return {"error": f"YouTube API {r.status_code}", "detail": r.text[:200]}
    data = r.json()
    vid_id = data.get("id")
    return {"mode": "live", "platform": "youtube", "video_id": vid_id,
            "url": f"https://youtu.be/{vid_id}" if vid_id else None}
