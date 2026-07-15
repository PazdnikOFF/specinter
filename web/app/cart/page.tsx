"use client";
import { useEffect, useState } from "react";
import Link from "next/link";
import { getCart, clearCart, saveCart, CartItem } from "../../lib/cart";
import CartStepper from "../CartStepper";
import ChannelPicker, { Channel } from "../ChannelPicker";
import CompanyByInn from "../CompanyByInn";
import DeliveryBlock, { Delivery } from "../DeliveryBlock";

const API = "";  // относительный путь через прокси Next.js (/api/* → бэкенд)

export default function CartPage() {
  const [items, setItems] = useState<CartItem[]>([]);
  const [kind, setKind] = useState<"person" | "legal">("person");
  const [form, setForm] = useState({ name: "", org_name: "", inn: "", kpp: "" });
  const [chan, setChan] = useState<{ channel: Channel; ref: string }>({ channel: "phone", ref: "" });
  const [delivery, setDelivery] = useState<Delivery>({ mode: "delivery", city: "" });
  const [result, setResult] = useState<any>(null);
  const [quoteResult, setQuoteResult] = useState<any>(null);
  const [busy, setBusy] = useState<"" | "order" | "quote">("");

  useEffect(() => {
    const sync = () => setItems(getCart());
    sync();
    window.addEventListener("cart-changed", sync);
    return () => window.removeEventListener("cart-changed", sync);
  }, []);

  // Заказ — только позиции с ценой > 0; всё остальное (цена 0/нет или kind=quote) — заявка.
  const isOrder = (i: CartItem) => (i.kind ?? "order") === "order" && i.price > 0;
  const orderItems = items.filter(isOrder);
  const quoteItems = items.filter((i) => !isOrder(i));
  const total = orderItems.reduce((s, i) => s + i.price * i.qty, 0);

  const stepper = (i: CartItem) => (
    <span style={{ width: 132, display: "inline-block" }}>
      <CartStepper product={{ product_id: i.product_id, article: i.article, name: i.name, price: i.price }}
        quote={i.kind === "quote"} />
    </span>
  );

  async function checkout(e: React.FormEvent) {
    e.preventDefault();
    setBusy("order");
    try {
      const r = await fetch(`${API}/api/orders`, {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          customer: { name: form.name, phone: chan.ref, kind,
            org_name: kind === "legal" ? form.org_name : null,
            inn: kind === "legal" ? form.inn : null,
            kpp: kind === "legal" ? form.kpp : null,
            contact_channel: chan.channel, contact_ref: chan.ref,
            email: chan.channel === "email" ? chan.ref : null },
          channel: chan.channel === "phone" ? "web" : chan.channel,
          delivery,
          items: orderItems.map((i) => ({ product_id: i.product_id, qty: i.qty })),
        }),
      });
      const d = await r.json();
      setResult(d);
      saveCart(items.filter((i) => i.kind === "quote"));  // оставляем только запросы
    } catch {
      setResult({ error: "Не удалось оформить заказ" });
    } finally { setBusy(""); }
  }

  async function requestQuote() {
    if (!form.name || !chan.ref.trim()) { alert("Укажите имя и контакт для обратной связи"); return; }
    setBusy("quote");
    try {
      const r = await fetch(`${API}/api/quote-requests`, {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: form.name, phone: chan.ref, auto_process: true,
          channel: chan.channel === "phone" ? "web" : chan.channel,
          contact_ref: chan.ref,
          email: chan.channel === "email" ? chan.ref : null,
          items: quoteItems.map((i) => ({ product_id: i.product_id, article: i.article,
            name: i.name, qty: i.qty })),
        }),
      });
      const d = await r.json();
      setQuoteResult(d);
      saveCart(items.filter((i) => (i.kind ?? "order") === "order"));  // запросы отправлены
    } catch {
      setQuoteResult({ error: "Не удалось отправить запрос" });
    } finally { setBusy(""); }
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
      {items.length === 0 && !quoteResult ? (
        <div className="empty">Корзина пуста. <Link className="link" href="/catalog">Перейти в каталог</Link></div>
      ) : (
        <>
          {/* ============ ЗАКАЗ (позиции с ценой) ============ */}
          {orderItems.length > 0 && (
            <section className="cart-block cart-order">
              <h2 className="section" style={{ marginTop: 0 }}>🛒 Заказ <span className="muted" style={{ fontSize: 14 }}>— позиции с ценой</span></h2>
              {orderItems.map((i) => (
                <div className="offer" key={i.product_id}>
                  <span><b>{i.article}</b> · {i.name}</span>
                  <span className="offer-buy">
                    {stepper(i)}
                    <b style={{ minWidth: 90, textAlign: "right" }}>{(i.price * i.qty).toLocaleString("ru-RU")} ₽</b>
                  </span>
                </div>
              ))}
              <div style={{ textAlign: "right", fontSize: 20, fontWeight: 600, margin: "12px 0 0" }}>
                Итого: {total.toLocaleString("ru-RU")} ₽
              </div>
            </section>
          )}

          {/* ============ ЗАЯВКА (позиции без цены — запрос) ============ */}
          {quoteItems.length > 0 && (
            <section className="cart-block cart-quote">
              <h2 className="section" style={{ marginTop: 0 }}>📝 Заявка на подбор <span className="muted" style={{ fontSize: 14 }}>— уточним цену и наличие</span></h2>
              {quoteItems.map((i) => (
                <div className="offer" key={i.product_id}>
                  <span><b>{i.article}</b> · {i.name}</span>
                  <span className="offer-buy">
                    {stepper(i)}
                    <span className="muted" style={{ minWidth: 90, textAlign: "right" }}>кол-во {i.qty}</span>
                  </span>
                </div>
              ))}
            </section>
          )}

          {quoteResult && (
            <div className="panel" style={{ marginBottom: 20 }}>
              {quoteResult.error ? <p>{quoteResult.error}</p> : <>
                <h2 className="section" style={{ marginTop: 0 }}>Ответ по заявке</h2>
                <pre style={{ whiteSpace: "pre-wrap", fontFamily: "inherit", margin: 0 }}>{quoteResult.response}</pre>
                <p className="muted" style={{ marginTop: 10, fontSize: 13 }}>
                  Заявка №{quoteResult.id}. С вами свяжутся по указанному контакту.
                </p>
              </>}
            </div>
          )}

          {/* ============ Контакты + действия ============ */}
          {items.length > 0 && (
            <form className="panel" onSubmit={(e) => { e.preventDefault(); if (orderItems.length > 0) checkout(e); else requestQuote(); }}>
              <h2 className="section" style={{ marginTop: 0 }}>Контактные данные</h2>
              <input className="fld" required placeholder="Имя / контактное лицо"
                value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
              <ChannelPicker onChange={setChan} />

              {/* Реквизиты/доставка нужны ТОЛЬКО при оформлении заказа */}
              {orderItems.length > 0 && (
                <>
                  <div style={{ display: "flex", gap: 16, margin: "12px 0" }}>
                    <label><input type="radio" checked={kind === "person"} onChange={() => setKind("person")} /> Физлицо</label>
                    <label><input type="radio" checked={kind === "legal"} onChange={() => setKind("legal")} /> Юрлицо (счёт)</label>
                  </div>
                  {kind === "legal" && (
                    <CompanyByInn onSelect={(c) => setForm((f) => ({ ...f,
                      org_name: c.name_short || c.name_full || "", inn: c.inn || "", kpp: c.kpp || "" }))} />
                  )}
                  <DeliveryBlock items={orderItems.map((i) => ({ product_id: i.product_id, qty: i.qty }))}
                    onChange={setDelivery} />
                  {delivery.mode === "delivery" && delivery.cost_rub != null && (
                    <div style={{ textAlign: "right", fontSize: 18, fontWeight: 600, margin: "8px 0" }}>
                      К оплате с доставкой: {(total + delivery.cost_rub).toLocaleString("ru-RU")} ₽
                    </div>
                  )}
                  {delivery.mode === "pickup" && (
                    <div style={{ textAlign: "right", fontSize: 15, color: "var(--muted)", margin: "8px 0" }}>
                      Самовывоз — бесплатно
                    </div>
                  )}
                </>
              )}

              {/* Действия */}
              <div style={{ display: "flex", flexWrap: "wrap", gap: 10, marginTop: 12 }}>
                {orderItems.length > 0 && (
                  <button className="btn-primary" disabled={busy !== ""} type="submit">
                    {busy === "order" ? "Оформляем…" : kind === "legal" ? "Оформить и получить счёт" : "Оформить заказ"}
                  </button>
                )}
                {quoteItems.length > 0 && (
                  <button className={orderItems.length > 0 ? "btn-secondary" : "btn-primary"}
                    disabled={busy !== ""} type={orderItems.length > 0 ? "button" : "submit"}
                    onClick={orderItems.length > 0 ? requestQuote : undefined}>
                    {busy === "quote" ? "Отправляем…" : orderItems.length > 0 ? "Отправить заявку на подбор" : "Отправить заявку"}
                  </button>
                )}
              </div>
              {orderItems.length === 0 && quoteItems.length > 0 && (
                <p className="muted" style={{ fontSize: 12, marginTop: 8 }}>
                  Это заявка на подбор цены — ИНН не требуется, для физ- и юрлиц одинаково.
                </p>
              )}
            </form>
          )}
        </>
      )}
    </main>
  );
}
