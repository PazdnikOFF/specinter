"use client";
import { useRouter } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import { thumbUrl } from "../lib/api";

type Hit = {
  id: number;
  manufacturer_article?: string | null;
  name?: string | null;
  brand?: string | null;
  primary_image?: string | null;
  min_price?: number | null;
  in_stock?: boolean;
};

export default function SearchBox({ initial = "" }: { initial?: string }) {
  const [q, setQ] = useState(initial);
  const [hits, setHits] = useState<Hit[]>([]);
  const [total, setTotal] = useState(0);
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(-1);
  const boxRef = useRef<HTMLDivElement>(null);
  const router = useRouter();

  // Живой поиск: debounce + отмена устаревших запросов (оптимизировано).
  useEffect(() => {
    const term = q.trim();
    if (term.length < 2) {
      setHits([]);
      setTotal(0);
      return;
    }
    const ctrl = new AbortController();
    const t = setTimeout(async () => {
      try {
        // Относительный путь: Next.js проксирует /api/* на бэкенд (см. next.config.js).
        // Работает при любом адресе доступа (localhost / IP / имя машины), без CORS.
        const r = await fetch(
          `/api/search?q=${encodeURIComponent(term)}&limit=8`,
          { signal: ctrl.signal }
        );
        if (!r.ok) return;
        const d = await r.json();
        setHits(d.hits ?? []);
        setTotal(d.total ?? 0);
        setOpen(true);
        setActive(-1);
      } catch {
        /* отменённый запрос — игнорируем */
      }
    }, 180);
    return () => {
      clearTimeout(t);
      ctrl.abort();
    };
  }, [q]);

  // Клик вне — закрыть выпадашку.
  useEffect(() => {
    const onDoc = (e: MouseEvent) => {
      if (boxRef.current && !boxRef.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener("mousedown", onDoc);
    return () => document.removeEventListener("mousedown", onDoc);
  }, []);

  const go = (term: string) => {
    if (term.trim()) {
      setOpen(false);
      router.push(`/search?q=${encodeURIComponent(term.trim())}`);
    }
  };

  const onKey = (e: React.KeyboardEvent) => {
    if (!open || hits.length === 0) return;
    if (e.key === "ArrowDown") { e.preventDefault(); setActive((a) => Math.min(a + 1, hits.length - 1)); }
    else if (e.key === "ArrowUp") { e.preventDefault(); setActive((a) => Math.max(a - 1, -1)); }
    else if (e.key === "Enter" && active >= 0) { e.preventDefault(); router.push(`/product/${hits[active].id}`); setOpen(false); }
    else if (e.key === "Escape") setOpen(false);
  };

  return (
    <div className="searchwrap" ref={boxRef}>
      <form
        className="searchbar"
        onSubmit={(e) => { e.preventDefault(); go(q); }}
      >
        <input
          autoFocus
          value={q}
          onChange={(e) => setQ(e.target.value)}
          onFocus={() => hits.length && setOpen(true)}
          onKeyDown={onKey}
          placeholder="Артикул, аналог или название детали…"
          aria-label="Поиск"
          autoComplete="off"
        />
        <button type="submit">Найти</button>
      </form>

      {open && hits.length > 0 && (
        <div className="suggest">
          {hits.map((h, i) => (
            <button
              type="button"
              key={h.id}
              className={`sug${i === active ? " active" : ""}`}
              onMouseEnter={() => setActive(i)}
              onClick={() => { router.push(`/product/${h.id}`); setOpen(false); }}
            >
              <span className="sug-thumb">
                {h.primary_image ? <img src={thumbUrl(h.primary_image)!} alt="" loading="lazy" /> : null}
              </span>
              <span className="sug-body">
                <span className="sug-art">{h.manufacturer_article || "—"}</span>
                <span className="sug-name">{h.name || "Без названия"}</span>
                <span className="sug-meta">
                  {h.brand && <span className="sug-brand">{h.brand}</span>}
                  <span className={`badge ${h.in_stock ? "in" : "out"}`}>
                    {h.in_stock ? "в наличии" : "под заказ"}
                  </span>
                </span>
              </span>
              <span className="sug-price">
                {h.min_price ? `${Math.round(h.min_price).toLocaleString("ru-RU")} ₽` : "по запросу"}
              </span>
            </button>
          ))}
          <button type="button" className="sug-all" onClick={() => go(q)}>
            Показать все результаты ({total.toLocaleString("ru-RU")}) →
          </button>
        </div>
      )}
    </div>
  );
}
