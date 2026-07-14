"use client";
import { useState } from "react";
import { addToCart } from "../lib/cart";

export default function AddToCart({ product }: {
  product: { product_id: number; article: string; name: string; price: number };
}) {
  const [added, setAdded] = useState(false);
  return (
    <button
      className="btn-primary"
      onClick={() => {
        addToCart({ ...product, qty: 1 });
        setAdded(true);
        setTimeout(() => setAdded(false), 1500);
      }}
    >
      {added ? "Добавлено ✓" : "В корзину"}
    </button>
  );
}
