"use client";
import { useState } from "react";
import Link from "next/link";
import { addToCart } from "../../lib/cart";

// Быстрый заказ: вставил список артикулов → сразу в корзину. Минимум кликов для B2B-повторников.
export default function QuickOrderPage() {
  const [text, setText] = useState("");
  const [res, setRes] = useState<any>(null);
  const [loading, setLoading] = useState(false);
  const [added, setAdded] = useState(0);

  async function resolve() {
    setLoading(true); setAdded(0);
    try {
      const r = await fetch("/api/quick-resolve", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ text }),
      });
      setRes(await r.json());
    } finally { setLoading(false); }
  }

  function addAll() {
    const found = (res?.items || []).filter((i: any) => i.matched);
    found.forEach((i: any) => addToCart({
      product_id: i.id, article: i.manufacturer_article, name: i.name,
      price: i.price ? Math.round(i.price) : 0, qty: i.qty,
      kind: i.price ? "order" : "quote",
    }));
    setAdded(found.length);
  }

  return (
    <main className="container" style={{ maxWidth: 720 }}>
      <h1 style={{ fontSize: 26, fontWeight: 600, margin: "28px 0 6px" }}>Быстрый заказ</h1>
      <p className="muted" style={{ marginBottom: 14 }}>
        Вставьте артикулы — по одному в строке. Можно с количеством: <code>16Y-01-00003 4</code>
      </p>
      <textarea value={text} onChange={(e) => setText(e.target.value)} rows={8}
        placeholder={"16Y-01-00003 2\nDZ9114940040\n..."}
        style={{ width: "100%", padding: 12, borderRadius: 10, border: "1px solid var(--line)",
                 fontFamily: "ui-monospace, monospace", fontSize: 14, background: "var(--surface)", color: "inherit" }} />
      <div style={{ display: "flex", gap: 10, alignItems: "center", margin: "10px 0" }}>
        <button className="btn" onClick={resolve} disabled={loading || !text.trim()}>
          {loading ? "Ищем…" : "Найти"}
        </button>
        {res && <span className="muted">Найдено {res.found} · не найдено {res.missing}</span>}
      </div>

      {res && (
        <>
          <div style={{ display: "flex", flexDirection: "column", gap: 6, margin: "8px 0 16px" }}>
            {res.items.map((i: any, k: number) => (
              <div key={k} className="offer" style={{ opacity: i.matched ? 1 : 0.6 }}>
                <div className="offer-info" style={{ minWidth: 0 }}>
                  <span className="art">{i.matched ? i.manufacturer_article : i.input}</span>
                  {i.matched
                    ? <span className="muted" style={{ overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{i.name}</span>
                    : <span className="muted">не найдено</span>}
                </div>
                <div className="offer-buy" style={{ gap: 8 }}>
                  <span className="muted">×{i.qty}</span>
                  {i.matched ? (
                    <>
                      <span className={`badge ${i.in_stock ? "in" : "out"}`}>{i.in_stock ? "в наличии" : "под заказ"}</span>
                      <span className="price">{i.price ? `${Math.round(i.price).toLocaleString("ru-RU")} ₽` : "по запросу"}</span>
                    </>
                  ) : (
                    <Link href={`/search?q=${encodeURIComponent(i.input)}`} className="link">искать</Link>
                  )}
                </div>
              </div>
            ))}
          </div>

          {res.found > 0 && added === 0 && (
            <button className="btn" onClick={addAll}>Добавить всё в корзину ({res.found})</button>
          )}
          {added > 0 && (
            <div className="offer offer-request" style={{ justifyContent: "space-between" }}>
              <span>Добавлено {added} позиц. в корзину.</span>
              <Link href="/cart" className="btn">Перейти в корзину →</Link>
            </div>
          )}
        </>
      )}
    </main>
  );
}
