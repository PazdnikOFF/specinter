from fastapi import APIRouter
from .. import db

router = APIRouter(prefix="/api/admin", tags=["admin: metrics"])


@router.get("/metrics")
async def metrics():
    """Сводные метрики портала для админ-дашборда."""
    async def one(sql, params=()):
        r = await db.fetchone(sql, params)
        return list(r.values())[0] if r else None

    catalog = {
        "products": await one("SELECT count(*) FROM products"),
        "with_article": await one("SELECT count(*) FROM products WHERE normalized_article IS NOT NULL"),
        "analogs": await one("SELECT count(*) FROM analogs"),
        "categories": await one("SELECT count(*) FROM categories WHERE visible"),
        "with_photo": await one("SELECT count(DISTINCT product_id) FROM product_images"),
    }
    offers = {
        "total": await one("SELECT count(*) FROM offers"),
        "in_stock": await one("SELECT count(*) FROM offers WHERE in_stock"),
        "from_price": await one("SELECT count(*) FROM offers WHERE source='price'"),
    }
    sp_total = await one("SELECT count(*) FROM supplier_prices") or 0
    sp_matched = await one(
        "SELECT count(*) FROM supplier_prices WHERE match_method IN ('exact_article','analog','manual')") or 0
    prices = {
        "suppliers": await one("SELECT count(*) FROM suppliers"),
        "price_rows": sp_total,
        "matched": sp_matched,
        "match_coverage_pct": round(100 * sp_matched / sp_total, 1) if sp_total else 0,
        "unmatched_queue": await one(
            "SELECT count(*) FROM supplier_prices WHERE match_method IS NULL OR match_method='fuzzy'"),
    }
    revenue = await one("SELECT COALESCE(sum(total),0) FROM orders WHERE status='paid'") or 0
    paid_cnt = await one("SELECT count(*) FROM orders WHERE status='paid'") or 0
    orders = {
        "total": await one("SELECT count(*) FROM orders"),
        "paid": paid_cnt,
        "revenue_rub": float(revenue),
        "avg_check_rub": round(float(revenue) / paid_cnt) if paid_cnt else 0,
        "by_status": await db.fetch("SELECT status, count(*) AS n FROM orders GROUP BY status"),
        "by_channel": await db.fetch("SELECT channel, count(*) AS n FROM orders GROUP BY channel"),
    }
    docs = {
        "invoices": await one("SELECT count(*) FROM documents WHERE kind='invoice'"),
        "edo_received": await one("SELECT count(*) FROM documents WHERE edo_status='received'"),
    }
    top = await db.fetch(
        """SELECT article, name, sum(qty) AS qty, count(*) AS orders
           FROM order_items GROUP BY article, name ORDER BY qty DESC LIMIT 10""")
    recent = await db.fetch(
        """SELECT o.id, o.total, o.status, o.channel, o.created_at, c.name AS customer
           FROM orders o LEFT JOIN customers c ON c.id=o.customer_id
           ORDER BY o.created_at DESC LIMIT 10""")

    return {"catalog": catalog, "offers": offers, "prices": prices,
            "orders": orders, "documents": docs, "top_products": top, "recent_orders": recent}
