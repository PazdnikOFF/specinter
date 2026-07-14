"use client";
import CartStepper from "./CartStepper";

// Тонкая обёртка над общим CartStepper для карточки товара (крупный вид).
export default function AddToCart({ product }: {
  product: { product_id: number; article: string; name: string; price: number };
}) {
  return <CartStepper product={product} big />;
}
