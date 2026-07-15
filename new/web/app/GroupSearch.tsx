"use client";
import { useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { thumbUrl } from "../lib/api";

// Живой поиск ВНУТРИ группы (по поддереву) с выпадающим списком найденных товаров.
// Ищет по названию, артикулу и бренду (фильтр на бэкенде).
export default function GroupSearch({ category, nodeName, initial = "" }: {
  category: string | number; nodeName: string; initial?: string;
}) {
  const [q, setQ] = useState(initial);
  const [hits, setHits] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(-1);
  const box = useRef<HTMLDivElement>(null);
  const router = useRouter();

  useEffect(() => {
    const term = q.trim();
    if (term.length < 2) { setHits([]); setTotal(0); return; }
    const ctrl = new AbortController();
    const t = setTimeout(async () => {
      try {
        const r = await fetch(
          `/api/catalog/browse?category=${category}&q=${encodeURIComponent(term)}&per_page=8`,
          { signal: ctrl.signal });
        if (!r.ok) return;
        const d = await r.json();
        setHits(d.products || []); setTotal(d.total || 0); setOpen(true); setActive(-1);
      } catch { /* отменён */ }
    }, 180);
    return () => { clearTimeout(t); ctrl.abort(); };
  }, [q, category]);

  useEffect(() => {
    const onDoc = (e: MouseEvent) => { if (box.current && !box.current.contains(e.target as Node)) setOpen(false); };
    document.addEventListener("mousedown", onDoc);
    return () => document.removeEventListener("mousedown", onDoc);
  }, []);

  const go = () => { if (q.trim()) { setOpen(false); router.push(`/catalog?cat=${category}&q=${encodeURIComponent(q.trim())}`); } };
  const onKey = (e: React.KeyboardEvent) => {
    if (!open || hits.length === 0) return;
    if (e.key === "ArrowDown") { e.preventDefault(); setActive((a) => Math.min(a + 1, hits.length - 1)); }
    else if (e.key === "ArrowUp") { e.preventDefault(); setActive((a) => Math.max(a - 1, -1)); }
    else if (e.key === "Enter" && active >= 0) { e.preventDefault(); router.push(`/product/${hits[active].id}`); setOpen(false); }
    else if (e.key === "Escape") setOpen(false);
  };

  return (
    <div className="searchwrap wide" ref={box} style={{ margin: "8px 0 4px" }}>
      <form className="searchbar" onSubmit={(e) => { e.preventDefault(); go(); }}>
        <input value={q} onChange={(e) => setQ(e.target.value)} autoComplete="off"
          onFocus={() => hits.length && setOpen(true)} onKeyDown={onKey}
          placeholder={`Поиск в «${nodeName}» по названию, артикулу или бренду…`} />
        <button type="submit">Найти</button>
      </form>
      {open && hits.length > 0 && (
        <div className="suggest">
          {hits.map((h, i) => (
            <button type="button" key={h.id} className={`sug${i === active ? " active" : ""}`}
              onMouseEnter={() => setActive(i)} onClick={() => { router.push(`/product/${h.id}`); setOpen(false); }}>
              <span className="sug-thumb">{h.primary_image ? <img src={thumbUrl(h.primary_image)!} alt="" loading="lazy" /> : null}</span>
              <span className="sug-body">
                <span className="sug-art">{h.manufacturer_article || "—"}{h.position ? ` · поз. ${h.position}` : ""}</span>
                <span className="sug-name">{h.name || "Без названия"}</span>
              </span>
              <span className="sug-price">{h.min_price ? `${Math.round(h.min_price).toLocaleString("ru-RU")} ₽` : "по запросу"}</span>
            </button>
          ))}
          <button type="button" className="sug-all" onClick={go}>Показать все ({total.toLocaleString("ru-RU")}) →</button>
        </div>
      )}
    </div>
  );
}
