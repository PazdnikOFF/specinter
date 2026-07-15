"use client";
import { useState } from "react";
import Link from "next/link";
import { thumbUrl } from "../lib/api";
import CartStepper from "./CartStepper";

type Prod = {
  id: number; manufacturer_article: string | null; name: string | null;
  brand?: string | null; primary_image: string | null;
  min_price: number | null; in_stock: boolean | null;
  position?: string | null; eta_days?: number | null;
};

// ОДНА карточка на позицию схемы (единый стиль с остальными карточками каталога).
// Показывает представителя (самый дешёвый вариант), цену «от N ₽» по минимуму
// среди самого товара и его аналогов, а все аналоги раскрываются внутри карточки.
export default function PositionCard({ position, products }: { position: string; products: Prod[] }) {
  const [open, setOpen] = useState(false);

  const priced = products.filter((p) => p.min_price && p.min_price > 0);
  // Представитель для «шапки» карточки — самый дешёвый вариант, иначе первый.
  const rep = priced.length
    ? priced.reduce((a, b) => (b.min_price! < a.min_price! ? b : a))
    : products[0];
  const minPrice = priced.length ? Math.min(...priced.map((p) => p.min_price!)) : 0;
  const anyStock = products.some((p) => p.in_stock);
  const noPrice = minPrice <= 0;
  const repPrice = Math.round(minPrice);

  return (
    <div className="card">
      <Link href={`/product/${rep.id}`} className="card-body">
        <div className="thumb">
          {rep.primary_image
            ? <img src={thumbUrl(rep.primary_image)!} alt={rep.name || ""} loading="lazy" decoding="async" />
            : <span>нет фото</span>}
        </div>
        <div className="art">
          {rep.manufacturer_article || "—"}
          {position && <span className="card-pos" style={{ marginLeft: 8 }}>поз. {position}</span>}
        </div>
        <div className="name">{rep.name || "Без названия"}</div>
      </Link>

      {/* Аналоги — раскрывающийся список внутри той же карточки */}
      <button type="button" className="analogs-toggle" onClick={() => setOpen((v) => !v)} aria-expanded={open}>
        {open ? "▾" : "▸"} На этой позиции: {products.length}
      </button>
      {open && (
        <div className="card-analogs">
          {products.map((p) => {
            const pr = p.min_price ? Math.round(p.min_price) : 0;
            const np = !(p.min_price && p.min_price > 0);
            return (
              <div className="card-analog" key={p.id}>
                <Link href={`/product/${p.id}`} className="card-analog-info">
                  <span className="art">{p.manufacturer_article || "—"}</span>
                  <span className="card-analog-name">{p.name || "Без названия"}</span>
                  <span className="muted" style={{ fontSize: 11 }}>
                    {np ? "цена по запросу" : `${pr.toLocaleString("ru-RU")} ₽`}
                    {" · "}{p.in_stock ? "в наличии" : "под заказ"}
                    {p.eta_days != null ? ` · ~${p.eta_days} дн.` : ""}
                  </span>
                </Link>
                <span className="card-analog-buy">
                  <CartStepper
                    product={{ product_id: p.id, article: p.manufacturer_article || String(p.id), name: p.name || "", price: pr }}
                    quote={np} addLabel="+" />
                </span>
              </div>
            );
          })}
        </div>
      )}

      <div className="card-foot">
        <div className="meta">
          {!noPrice ? (
            <span className="price">от {repPrice.toLocaleString("ru-RU")} ₽</span>
          ) : null}
          <span className={`badge ${anyStock ? "in" : "out"}`}>
            {anyStock ? "в наличии" : "под заказ"}
          </span>
        </div>
        <CartStepper
          product={{ product_id: rep.id, article: rep.manufacturer_article || String(rep.id), name: rep.name || "", price: repPrice }}
          quote={noPrice} />
      </div>
    </div>
  );
}
