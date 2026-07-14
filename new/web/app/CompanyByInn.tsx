"use client";
import { useEffect, useRef, useState } from "react";

export type Company = {
  inn?: string; kpp?: string; ogrn?: string; okpo?: string;
  name_short?: string; name_full?: string; opf?: string;
  management_name?: string; management_post?: string;
  address?: string; postal_code?: string; status?: string; city?: string; kladr_id?: string;
};

// Подбор компании по ИНН через DaData: ввод ИНН → список (КПП/наименование) →
// выбор → заполнение реквизитов и показ полных данных (адрес, подписант и пр.).
export default function CompanyByInn({ onSelect }: { onSelect: (c: Company) => void }) {
  const [inn, setInn] = useState("");
  const [list, setList] = useState<Company[]>([]);
  const [open, setOpen] = useState(false);
  const [picked, setPicked] = useState<Company | null>(null);
  const box = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const term = inn.replace(/\D/g, "");
    if (term.length < 8) { setList([]); return; }
    const ctrl = new AbortController();
    const t = setTimeout(async () => {
      try {
        const r = await fetch(`/api/dadata/party?inn=${term}`, { signal: ctrl.signal });
        if (!r.ok) return;
        const d = await r.json();
        setList(d.suggestions || []);
        setOpen(true);
      } catch { /* отменён */ }
    }, 250);
    return () => { clearTimeout(t); ctrl.abort(); };
  }, [inn]);

  useEffect(() => {
    const onDoc = (e: MouseEvent) => { if (box.current && !box.current.contains(e.target as Node)) setOpen(false); };
    document.addEventListener("mousedown", onDoc);
    return () => document.removeEventListener("mousedown", onDoc);
  }, []);

  const choose = (c: Company) => {
    setPicked(c); setOpen(false);
    setInn(c.inn || inn);
    onSelect(c);
  };

  return (
    <div className="searchwrap" ref={box}>
      <input className="fld" placeholder="ИНН организации (подтянем реквизиты)"
        value={inn} inputMode="numeric" autoComplete="off"
        onChange={(e) => { setInn(e.target.value); setPicked(null); }}
        onFocus={() => list.length && setOpen(true)} />
      {open && list.length > 0 && (
        <div className="suggest">
          {list.map((c, i) => (
            <button type="button" key={i} className="sug" onClick={() => choose(c)}>
              <span className="sug-body">
                <span className="sug-art">{c.name_short || c.name_full}</span>
                <span className="sug-name">ИНН {c.inn}{c.kpp ? ` · КПП ${c.kpp}` : ""}{c.city ? ` · ${c.city}` : ""}</span>
              </span>
              {c.status && c.status !== "ACTIVE" &&
                <span className="badge out">{c.status === "LIQUIDATED" ? "ликвид." : c.status}</span>}
            </button>
          ))}
        </div>
      )}
      {picked && (
        <div className="panel" style={{ marginTop: 10, padding: 14 }}>
          <div style={{ fontWeight: 600 }}>{picked.name_full || picked.name_short}</div>
          <table className="tbl" style={{ marginTop: 8 }}>
            <tbody>
              <tr><td className="muted">ИНН / КПП</td><td>{picked.inn} / {picked.kpp || "—"}</td></tr>
              {picked.ogrn && <tr><td className="muted">ОГРН</td><td>{picked.ogrn}</td></tr>}
              <tr><td className="muted">Адрес</td><td>{picked.address || "—"}</td></tr>
              {(picked.management_name || picked.management_post) &&
                <tr><td className="muted">Подписант</td><td>{picked.management_post} {picked.management_name}</td></tr>}
              {picked.status && <tr><td className="muted">Статус</td><td>{picked.status === "ACTIVE" ? "действующая" : picked.status}</td></tr>}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
