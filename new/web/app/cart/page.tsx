"use client";
import { useEffect, useState } from "react";
import Link from "next/link";
import { getCart, setQty, clearCart, CartItem } from "../../lib/cart";

const API = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8010";

export default function CartPage() {
  const [items, setItems] = useState<CartItem[]>([]);
  const [kind, setKind] = useState<"person" | "legal">("person");
  const [form, setForm] = useState({ name: "", phone: "", org_name: "", inn: "" });
  const [result, setResult] = useState<any>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    const sync = () => setItems(getCart());
    sync();
    window.addEventListener("cart-changed", sync);
    return () => window.removeEventListener("cart-changed", sync);
  }, []);

  const total = items.reduce((s, i) => s + i.price * i.qty, 0);

  async function checkout(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    try {
      const r = await fetch(`${API}/api/orders`, {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          customer: { name: form.name, phone: form.phone, kind,
            org_name: kind === "legal" ? form.org_name : null,
            inn: kind === "legal" ? form.inn : null },
          channel: "web",
          items: items.map((i) => ({ product_id: i.product_id, qty: i.qty })),
        }),
      });
      const d = await r.json();
      setResult(d);
      clearCart();
      setItems([]);
    } catch {
      setResult({ error: "Не удалось оформить заказ" });
    } finally {
      setBusy(false);
    }
  }

  if (result) {
    return (
      <main className="container" style={{ maxWidth: 640 }}>
        <div className="panel" style={{ marginTop: 40, textAlign: "center" }}>
          {result.error ? <h1 className="section">{result.error}</h1> : <>
            <h1 style={{ fontSize: 26, fontWeight: 600 }}>Заказ №{result.order_id} оформлен</h1>
            <p className="muted" style={{ margin: "10px 0" }}>Сумма: {Math.round(result.total).toLocaleString("ru-RU")} ₽</p>
            {result.payment?.confirmation_url &&
              <p><a className="btn-primary" href={result.payment.confirmation_url}>Перейти к оплате</a></p>}
            {result.invoice_pdf_url &&
              <p style={{ marginTop: 12 }}>
                <a className="link" href={`${API}${result.invoice_pdf_url}`} target="_blank">Скачать счёт (PDF)</a>
              </p>}
          </>}
          <p style={{ marginTop: 20 }}><Link className="link" href="/catalog">← В каталог</Link></p>
        </div>
      </main>
    );
  }

  return (
    <main className="container" style={{ maxWidth: 760 }}>
      <h1 style={{ fontSize: 28, fontWeight: 600, margin: "32px 0 16px" }}>Корзина</h1>
      {items.length === 0 ? (
        <div className="empty">Корзина пуста. <Link className="link" href="/catalog">Перейти в каталог</Link></div>
      ) : (
        <>
          {items.map((i) => (
            <div className="offer" key={i.product_id}>
              <span><b>{i.article}</b> · {i.name}</span>
              <span style={{ display: "flex", gap: 14, alignItems: "center" }}>
                <input type="number" min={1} value={i.qty} style={{ width: 60 }}
                  onChange={(e) => setQty(i.product_id, parseInt(e.target.value) || 1)} />
                <b>{(i.price * i.qty).toLocaleString("ru-RU")} ₽</b>
              </span>
            </div>
          ))}
          <div style={{ textAlign: "right", fontSize: 20, fontWeight: 600, margin: "16px 0" }}>
            Итого: {total.toLocaleString("ru-RU")} ₽
          </div>

          <form className="panel" onSubmit={checkout}>
            <h2 className="section">Оформление</h2>
            <div style={{ display: "flex", gap: 16, marginBottom: 12 }}>
              <label><input type="radio" checked={kind === "person"} onChange={() => setKind("person")} /> Физлицо</label>
              <label><input type="radio" checked={kind === "legal"} onChange={() => setKind("legal")} /> Юрлицо (счёт)</label>
            </div>
            <input className="fld" required placeholder="Имя / контактное лицо"
              value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            <input className="fld" required placeholder="Телефон"
              value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
            {kind === "legal" && <>
              <input className="fld" placeholder="Организация"
                value={form.org_name} onChange={(e) => setForm({ ...form, org_name: e.target.value })} />
              <input className="fld" placeholder="ИНН"
                value={form.inn} onChange={(e) => setForm({ ...form, inn: e.target.value })} />
            </>}
            <button className="btn-primary" disabled={busy} type="submit">
              {busy ? "Оформляем…" : kind === "legal" ? "Оформить и получить счёт" : "Оформить и оплатить"}
            </button>
          </form>
        </>
      )}
    </main>
  );
}
