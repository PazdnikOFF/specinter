"use client";
// Простая корзина в localStorage (без бэкенда до оформления).
// kind: "order" — есть цена, оформляем заказ; "quote" — цены нет, запрашиваем.
export type CartKind = "order" | "quote";
export type CartItem = {
  product_id: number; article: string; name: string; price: number; qty: number; kind?: CartKind;
};

const KEY = "specinter_cart";

export function getCart(): CartItem[] {
  if (typeof window === "undefined") return [];
  try { return JSON.parse(localStorage.getItem(KEY) || "[]"); } catch { return []; }
}

export function saveCart(items: CartItem[]) {
  localStorage.setItem(KEY, JSON.stringify(items));
  window.dispatchEvent(new Event("cart-changed"));
}

export function addToCart(item: CartItem) {
  const cart = getCart();
  const it = { kind: "order" as CartKind, ...item };
  const ex = cart.find((c) => c.product_id === it.product_id);
  if (ex) { ex.qty += it.qty; ex.kind = it.kind; ex.price = it.price; }
  else cart.push(it);
  saveCart(cart);
}

export function setQty(product_id: number, qty: number) {
  const cart = getCart().map((c) => (c.product_id === product_id ? { ...c, qty } : c)).filter((c) => c.qty > 0);
  saveCart(cart);
}

export function clearCart() { saveCart([]); }
