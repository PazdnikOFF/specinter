"use client";
import Link from "next/link";
import { thumbUrl } from "../lib/api";
import CartStepper from "./CartStepper";

type Prod = {
  id: number; manufacturer_article: string | null; name: string | null;
  primary_image: string | null; min_price: number | null; in_stock: boolean | null;
  position?: string | null; eta_days?: number | null;
};

// Объединённая карточка ОДНОЙ позиции схемы, где несколько товаров (вариантов).
// Внутри — все варианты с ценой, наличием и сроком.
export default function PositionCard({ position, products }: { position: string; products: Prod[] }) {
  return (
    <div className="pos-card">
      <div className="pos-card-head">
        <span className="card-pos">поз. {position}</span>
        <span className="muted" style={{ fontSize: 12 }}>{products.length} вариантов</span>
      </div>
      {products.map((p) => {
        const price = p.min_price ? Math.round(p.min_price) : 0;
        const noPrice = !(p.min_price && p.min_price > 0);
        return (
          <div className="pos-prod" key={p.id}>
            <Link href={`/product/${p.id}`} className="pos-prod-thumb">
              {p.primary_image ? <img src={thumbUrl(p.primary_image)!} alt="" loading="lazy" /> : <span>—</span>}
            </Link>
            <Link href={`/product/${p.id}`} className="pos-prod-info">
              <span className="art">{p.manufacturer_article || "—"}</span>
              <span className="pos-prod-name">{p.name || "Без названия"}</span>
            </Link>
            <div className="pos-prod-buy">
              <div className="pos-prod-price">
                {!noPrice && <span className="price">{price.toLocaleString("ru-RU")} ₽</span>}
                <span className="muted" style={{ fontSize: 12 }}>
                  {p.in_stock ? "в наличии" : "под заказ"}
                  {p.eta_days != null ? ` · ~${p.eta_days} дн.` : ""}
                </span>
              </div>
              <span className="pos-cart">
                <CartStepper product={{ product_id: p.id, article: p.manufacturer_article || String(p.id),
                  name: p.name || "", price }} quote={noPrice} />
              </span>
            </div>
          </div>
        );
      })}
    </div>
  );
}
