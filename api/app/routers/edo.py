from fastapi import APIRouter
from .. import diadoc

router = APIRouter(prefix="/api/edo", tags=["ЭДО Диадок"])


@router.get("/status")
async def edo_status():
    return await diadoc.status()


@router.post("/sync")
async def edo_sync():
    """Забрать новые закрывающие документы из Диадок и сохранить."""
    return await diadoc.sync()
