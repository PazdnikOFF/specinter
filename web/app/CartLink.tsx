"use client";
import Link from "next/link";
import { useEffect, useState } from "react";
import { getCart } from "../lib/cart";

export default function CartLink() {
  const [n, setN] = useState(0);
  const [sum, setSum] = useState(0);
  useEffect(() => {
    const sync = () => {
      const cart = getCart();
      setN(cart.reduce((s, i) => s + i.qty, 0));
      // сумма только заказанных позиций (с ценой > 0); заявки/без цены не считаем
      setSum(cart.filter((i) => (i.kind ?? "order") === "order" && i.price > 0)
        .reduce((s, i) => s + i.price * i.qty, 0));
    };
    sync();
    window.addEventListener("cart-changed", sync);
    return () => window.removeEventListener("cart-changed", sync);
  }, []);
  return (
    <Link href="/cart" className="link cart-link">
      Корзина
      {n > 0 && <span className="cart-badge">{n}</span>}
      {sum > 0 && <span className="cart-sum">{sum.toLocaleString("ru-RU")} ₽</span>}
    </Link>
  );
}
