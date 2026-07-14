"use client";
import { useEffect, useState } from "react";
import Link from "next/link";
import { imgUrl } from "../lib/api";
import { getCart, addToCart, setQty } from "../lib/cart";

type P = {
  id: number;
  manufacturer_article: string | null;
  name: string | null;
  brand: string | null;
  primary_image: string | null;
  min_price: number | null;
  in_stock: boolean | null;
};

export default function CatalogCard({ p }: { p: P }) {
  const [qty, setQtyState] = useState(0);
  const price = p.min_price ? Math.round(p.min_price) : 0;

  useEffect(() => {
    const sync = () => setQtyState(getCart().find((c) => c.product_id === p.id)?.qty ?? 0);
    sync();
    window.addEventListener("cart-changed", sync);
    return () => window.removeEventListener("cart-changed", sync);
  }, [p.id]);

  const add = () =>
    addToCart({ product_id: p.id, article: p.manufacturer_article || String(p.id),
      name: p.name || "", price, qty: 1 });
  const step = (d: number) => setQty(p.id, qty + d);

  return (
    <div className="card">
      <Link href={`/product/${p.id}`} className="card-body">
        <div className="thumb">
          {p.primary_image ? <img src={imgUrl(p.primary_image)!} alt={p.name || ""} /> : <span>нет фото</span>}
        </div>
        <div className="art">{p.manufacturer_article || "—"}</div>
        <div className="name">{p.name || "Без названия"}</div>
      </Link>
      <div className="meta">
        <span className="price">
          {p.min_price ? `${price.toLocaleString("ru-RU")} ₽` : "по запросу"}
        </span>
        <span className={`badge ${p.in_stock ? "in" : "out"}`}>
          {p.in_stock ? "в наличии" : "под заказ"}
        </span>
      </div>
      {qty === 0 ? (
        <button className="cart-btn" onClick={add}>В корзину</button>
      ) : (
        <div className="stepper">
          <button onClick={() => step(-1)} aria-label="убрать">−</button>
          <span>{qty}</span>
          <button onClick={() => step(1)} aria-label="добавить">+</button>
        </div>
      )}
    </div>
  );
}
