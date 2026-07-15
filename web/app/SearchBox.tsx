"use client";
import { useRouter, useSearchParams, usePathname } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import { thumbUrl } from "../lib/api";

type Hit = {
  id: number; manufacturer_article?: string | null; name?: string | null;
  brand?: string | null; primary_image?: string | null; min_price?: number | null;
  in_stock?: boolean; position?: string | null;
};

// Единый умный поиск. На странице группы (/catalog?cat=…) слева появляется переключатель
// «в группе» (по умолчанию вкл) — поиск идёт по поддереву группы; иначе — глобальный.
export default function SearchBox({ initial = "", wide = false }: { initial?: string; wide?: boolean }) {
  const params = useSearchParams();
  const pathname = usePathname();
  const cat = pathname === "/catalog" ? params.get("cat") : null;
  const inGroup = !!cat;

  const [q, setQ] = useState(initial);
  const [scoped, setScoped] = useState(true);          // «в группе» по умолчанию включён
  const [hits, setHits] = useState<Hit[]>([]);
  const [total, setTotal] = useState(0);
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(-1);
  const boxRef = useRef<HTMLDivElement>(null);
  const router = useRouter();

  const useGroup = inGroup && scoped;

  // При входе в раздел (смене cat) кнопка возвращается в нажатое состояние — поиск по разделу.
  useEffect(() => { setScoped(true); }, [cat]);

  useEffect(() => {
    const term = q.trim();
    if (term.length < 2) { setHits([]); setTotal(0); return; }
    const ctrl = new AbortController();
    const t = setTimeout(async () => {
      try {
        const url = useGroup
          ? `/api/catalog/browse?category=${cat}&q=${encodeURIComponent(term)}&per_page=8`
          : `/api/search?q=${encodeURIComponent(term)}&limit=8`;
        const r = await fetch(url, { signal: ctrl.signal });
        if (!r.ok) return;
        const d = await r.json();
        setHits(useGroup ? (d.products || []) : (d.hits || []));
        setTotal(useGroup ? (d.total || 0) : (d.total || 0));
        setOpen(true); setActive(-1);
      } catch { /* отменён */ }
    }, 180);
    return () => { clearTimeout(t); ctrl.abort(); };
  }, [q, useGroup, cat]);

  useEffect(() => {
    const onDoc = (e: MouseEvent) => { if (boxRef.current && !boxRef.current.contains(e.target as Node)) setOpen(false); };
    document.addEventListener("mousedown", onDoc);
    return () => document.removeEventListener("mousedown", onDoc);
  }, []);

  const go = () => {
    const term = q.trim();
    if (!term) return;
    setOpen(false);
    router.push(useGroup ? `/catalog?cat=${cat}&q=${encodeURIComponent(term)}` : `/search?q=${encodeURIComponent(term)}`);
  };
  const onKey = (e: React.KeyboardEvent) => {
    if (!open || hits.length === 0) return;
    if (e.key === "ArrowDown") { e.preventDefault(); setActive((a) => Math.min(a + 1, hits.length - 1)); }
    else if (e.key === "ArrowUp") { e.preventDefault(); setActive((a) => Math.max(a - 1, -1)); }
    else if (e.key === "Enter" && active >= 0) { e.preventDefault(); router.push(`/product/${hits[active].id}`); setOpen(false); }
    else if (e.key === "Escape") setOpen(false);
  };

  return (
    <div className={`searchwrap${wide ? " wide" : ""}`} ref={boxRef}>
      <form className="searchbar" onSubmit={(e) => { e.preventDefault(); go(); }}>
        {inGroup && (
          <button type="button" aria-pressed={scoped}
            className={`scope-btn${scoped ? " scoped" : ""}`}
            onClick={() => setScoped((s) => !s)}
            title={scoped ? "Ищем только в текущем разделе — нажмите, чтобы искать по всему сайту" : "Ищем по всему сайту — нажмите, чтобы искать только в разделе"}>
            {scoped ? "по разделу" : "по сайту"}
          </button>
        )}
        <input autoFocus={!inGroup} value={q} onChange={(e) => setQ(e.target.value)}
          onFocus={() => hits.length && setOpen(true)} onKeyDown={onKey} autoComplete="off"
          placeholder={useGroup ? "Поиск в текущем разделе…" : "Артикул, аналог или название детали…"}
          aria-label="Поиск" />
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
                <span className="sug-meta">
                  {h.brand && <span className="sug-brand">{h.brand}</span>}
                  <span className={`badge ${h.in_stock ? "in" : "out"}`}>{h.in_stock ? "в наличии" : "под заказ"}</span>
                </span>
              </span>
              <span className="sug-price">{h.min_price ? `${Math.round(h.min_price).toLocaleString("ru-RU")} ₽` : "по запросу"}</span>
            </button>
          ))}
          <button type="button" className="sug-all" onClick={go}>
            Показать все ({total.toLocaleString("ru-RU")}){useGroup ? " в разделе" : ""} →
          </button>
        </div>
      )}
    </div>
  );
}
