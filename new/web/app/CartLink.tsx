"use client";
import Link from "next/link";
import { useEffect, useState } from "react";
import { getCart } from "../lib/cart";

export default function CartLink() {
  const [n, setN] = useState(0);
  useEffect(() => {
    const sync = () => setN(getCart().reduce((s, i) => s + i.qty, 0));
    sync();
    window.addEventListener("cart-changed", sync);
    return () => window.removeEventListener("cart-changed", sync);
  }, []);
  return (
    <Link href="/cart" className="link cart-link">
      Корзина{n > 0 && <span className="cart-badge">{n}</span>}
    </Link>
  );
}
