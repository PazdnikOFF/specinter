"use client";
import { useState } from "react";

const API = "";  // относительный путь через прокси Next.js (/api/* → бэкенд)

export default function QuoteForm({ query }: { query: string }) {
  const [form, setForm] = useState({ name: "", phone: "", comment: "" });
  const [done, setDone] = useState(false);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    await fetch(`${API}/api/quote-requests`, {
      method: "POST", headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ query, ...form, channel: "web" }),
    });
    setDone(true);
  }

  if (done) return <p className="hint" style={{ textAlign: "center" }}>Заявка принята — подберём и свяжемся с вами.</p>;

  return (
    <form className="panel" style={{ maxWidth: 480, margin: "0 auto" }} onSubmit={submit}>
      <h2 className="section" style={{ marginTop: 0 }}>Не нашли деталь? Оставьте заявку</h2>
      <p className="muted" style={{ marginBottom: 12 }}>Подберём по запросу «{query}» и свяжемся с вами.</p>
      <input className="fld" required placeholder="Имя"
        value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
      <input className="fld" required placeholder="Телефон"
        value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
      <input className="fld" placeholder="Комментарий (модель техники и т.п.)"
        value={form.comment} onChange={(e) => setForm({ ...form, comment: e.target.value })} />
      <button className="btn-primary" type="submit">Отправить заявку</button>
    </form>
  );
}
