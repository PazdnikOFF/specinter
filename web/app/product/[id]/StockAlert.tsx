"use client";
import { useState } from "react";
import { API_PUBLIC } from "../../../lib/api";

// Подписка «сообщить о поступлении» — перенос функционала старого сайта (it_b_notify).
// Показывается только когда товара нет в наличии (см. product/[id]/page.tsx).
export default function StockAlert({ productId }: { productId: number }) {
  const [open, setOpen] = useState(false);
  const [email, setEmail] = useState("");
  const [state, setState] = useState<"idle" | "sending" | "done" | "error">("idle");
  const [error, setError] = useState("");

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setState("sending");
    setError("");
    try {
      const r = await fetch(`${API_PUBLIC}/api/products/${productId}/stock-alert`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email }),
      });
      if (!r.ok) {
        const d = await r.json().catch(() => ({}));
        setError(d?.detail || "Не удалось оформить подписку");
        setState("error");
        return;
      }
      setState("done");
    } catch {
      setError("Сеть недоступна — попробуйте ещё раз");
      setState("error");
    }
  }

  if (state === "done") {
    return (
      <div className="muted" style={{ margin: "12px 0", fontSize: 14 }}>
        Готово — напишем на {email}, как только товар появится в наличии.
      </div>
    );
  }

  if (!open) {
    return (
      <button type="button" className="btn-secondary" onClick={() => setOpen(true)}
              style={{ margin: "12px 0" }}>
        Сообщить о поступлении
      </button>
    );
  }

  return (
    <form onSubmit={submit} style={{ margin: "12px 0", display: "flex", gap: 8, flexWrap: "wrap", alignItems: "flex-start" }}>
      <input
        className="fld" type="email" required value={email}
        onChange={(e) => setEmail(e.target.value)}
        placeholder="you@example.com" aria-label="Email для уведомления"
        style={{ flex: "1 1 200px", width: "auto", marginBottom: 0 }}
      />
      <button type="submit" className="btn" disabled={state === "sending"}>
        {state === "sending" ? "Отправляем…" : "Уведомить"}
      </button>
      {state === "error" && (
        <div className="muted" style={{ flexBasis: "100%", fontSize: 13 }}>{error}</div>
      )}
    </form>
  );
}
