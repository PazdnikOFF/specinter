"use client";
import { useEffect, useRef, useState } from "react";
import { getCart, addToCart, setQty } from "../lib/cart";

type Product = { product_id: number; article: string; name: string; price: number };

/**
 * Кнопка «В корзину» → после добавления превращается в степпер:
 * [− убрать одну] [редактируемое количество] [+ добавить]. При количестве 1
 * нажатие «−» убирает позицию из корзины.
 * Защита от «залипания»/дребезга мыши: повторные срабатывания < 250мс игнорируются,
 * поэтому застрявшая/дребезжащая кнопка мыши не накручивает количество.
 */
export default function CartStepper({
  product,
  addLabel,
  big = false,
  quote = false,
}: {
  product: Product;
  addLabel?: string;
  big?: boolean;
  quote?: boolean;   // true — режим «запросить цену» (позиция без цены)
}) {
  const label = addLabel ?? (quote ? "Запросить" : "В корзину");
  const [qty, setQtyState] = useState(0);
  const [draft, setDraft] = useState("");
  const guard = useRef(0);

  useEffect(() => {
    const sync = () => {
      const item = getCart().find((c) => c.product_id === product.product_id);
      setQtyState(item ? item.qty : 0);
    };
    sync();
    window.addEventListener("cart-changed", sync);
    return () => window.removeEventListener("cart-changed", sync);
  }, [product.product_id]);

  // Поле ввода следует за фактическим количеством, когда его меняют извне.
  useEffect(() => {
    setDraft(qty ? String(qty) : "");
  }, [qty]);

  // Пропускаем действие только если с прошлого прошло ≥250мс (гасим дребезг/дабл-клик).
  const once = (fn: () => void) => {
    const now = Date.now();
    if (now - guard.current < 250) return;
    guard.current = now;
    fn();
  };

  const add = () => once(() => addToCart({ ...product, qty: 1, kind: quote ? "quote" : "order" }));
  const inc = () => once(() => setQty(product.product_id, qty + 1));
  const dec = () => once(() => setQty(product.product_id, qty - 1)); // при 1 → 0 убирает позицию

  const commit = (raw: string) => {
    const n = Math.floor(Number(raw));
    if (!raw.trim() || Number.isNaN(n) || n < 1) {
      setDraft(String(qty || 1)); // пусто/мусор/0 — откатываем к текущему
      return;
    }
    setQty(product.product_id, n);
  };

  if (qty === 0) {
    return (
      <button type="button"
        className={`${big ? "cart-btn cart-btn-big" : "cart-btn"}${quote ? " cart-btn-quote" : ""}`}
        onClick={add}>
        {label}
      </button>
    );
  }

  return (
    <div className={big ? "stepper stepper-big" : "stepper"}>
      <button type="button" className="st-dec" onClick={dec} aria-label="Убрать одну" title="Убрать одну">
        −
      </button>
      <input
        className="st-qty"
        type="number"
        min={1}
        inputMode="numeric"
        value={draft}
        onChange={(e) => setDraft(e.target.value)}
        onBlur={(e) => commit(e.target.value)}
        onKeyDown={(e) => {
          if (e.key === "Enter") (e.target as HTMLInputElement).blur();
        }}
        aria-label="Количество в корзине"
      />
      <button type="button" className="st-inc" onClick={inc} aria-label="Добавить ещё одну">
        +
      </button>
    </div>
  );
}
