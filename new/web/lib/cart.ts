"use client";
// Простая корзина в localStorage (без бэкенда до оформления).
export type CartItem = { product_id: number; article: string; name: string; price: number; qty: number };

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
  const ex = cart.find((c) => c.product_id === item.product_id);
  if (ex) ex.qty += item.qty; else cart.push(item);
  saveCart(cart);
}

export function setQty(product_id: number, qty: number) {
  const cart = getCart().map((c) => (c.product_id === product_id ? { ...c, qty } : c)).filter((c) => c.qty > 0);
  saveCart(cart);
}

export function clearCart() { saveCart([]); }
