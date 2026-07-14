"use client";
import { useCallback, useEffect, useState } from "react";

type Item = { product_id: number; qty: number };
export type Delivery = {
  mode: "delivery" | "pickup";
  city: string; kladr_id?: string;
  cost_rub?: number; term_label?: string; address?: string;
};

const PICKUP_ADDRESS = "Екатеринбург, переулок Шофёров, 11";

// Доставка: за свой счёт (Деловые Линии, по умолчанию) или самовывоз со склада.
export default function DeliveryBlock({ items, onChange }: {
  items: Item[]; onChange: (d: Delivery) => void;
}) {
  const [mode, setMode] = useState<"delivery" | "pickup">("delivery");  // по умолчанию — доставка
  const [city, setCity] = useState("");
  const [kladr, setKladr] = useState<string | undefined>();
  const [est, setEst] = useState<any>(null);
  const [busy, setBusy] = useState(false);

  const sig = items.map((i) => `${i.product_id}:${i.qty}`).join(",");

  // Город по геолокации браузера (только для доставки).
  useEffect(() => {
    if (mode !== "delivery" || city) return;
    const fallback = () => fetch("/api/geo/city").then((r) => r.json())
      .then((d) => { if (d.city) { setCity(d.city); setKladr(d.kladr_id); } }).catch(() => {});
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (pos) => fetch(`/api/geo/city?lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`)
          .then((r) => r.json()).then((d) => { if (d.city) { setCity(d.city); setKladr(d.kladr_id); } })
          .catch(fallback),
        fallback, { timeout: 5000 });
    } else fallback();
  }, [mode, city]);

  const calc = useCallback(async () => {
    if (mode !== "delivery" || !city || items.length === 0) { setEst(null); return; }
    setBusy(true);
    try {
      const r = await fetch("/api/delivery/estimate", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ to_city: city, to_kladr: kladr, items }),
      });
      const d = await r.json();
      setEst(d.error ? null : d);
      onChange({ mode: "delivery", city, kladr_id: kladr, cost_rub: d.cost_rub, term_label: d.term_label });
    } finally { setBusy(false); }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [mode, city, kladr, sig]);

  useEffect(() => { calc(); }, [calc]);
  // Переключение на самовывоз — сразу отдаём выбор (бесплатно).
  useEffect(() => {
    if (mode === "pickup")
      onChange({ mode: "pickup", city: "Екатеринбург", address: PICKUP_ADDRESS, cost_rub: 0, term_label: "самовывоз" });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [mode]);

  return (
    <div style={{ margin: "12px 0" }}>
      <div className="muted" style={{ fontSize: 13, marginBottom: 6 }}>Способ получения</div>
      <div style={{ display: "flex", flexWrap: "wrap", gap: 14, marginBottom: 8 }}>
        <label style={{ display: "flex", alignItems: "center", gap: 6 }}>
          <input type="radio" checked={mode === "delivery"} onChange={() => setMode("delivery")} />
          Доставка за свой счёт (Деловые Линии)
        </label>
        <label style={{ display: "flex", alignItems: "center", gap: 6 }}>
          <input type="radio" checked={mode === "pickup"} onChange={() => setMode("pickup")} />
          Самовывоз
        </label>
      </div>

      {mode === "delivery" && (
        <>
          <div className="admbar">
            <input className="fld" style={{ margin: 0, maxWidth: 280 }} placeholder="Город доставки"
              value={city} onChange={(e) => { setCity(e.target.value); setKladr(undefined); }} />
            <button type="button" className="btn-secondary" onClick={calc} disabled={busy}>
              {busy ? "Считаем…" : "Пересчитать"}
            </button>
          </div>
          {est && (
            <div className="panel" style={{ marginTop: 8, padding: 14 }}>
              <b>{est.carrier}: {est.cost_rub?.toLocaleString("ru-RU")} ₽</b>
              <span className="muted"> · {est.term_label} · Екатеринбург → {est.to_city}</span>
              <div className="muted" style={{ fontSize: 12, marginTop: 4 }}>
                вес {est.weight_kg} кг, объём {est.volume_m3} м³
                {est.mode === "dry-run" ? " · ориентировочно (нет ключа API)" : ""}
              </div>
            </div>
          )}
        </>
      )}

      {mode === "pickup" && (
        <div className="panel" style={{ padding: 14 }}>
          <b>Самовывоз со склада — бесплатно</b>
          <div style={{ marginTop: 4 }}>{PICKUP_ADDRESS}</div>
          <div className="muted" style={{ fontSize: 13, marginTop: 4 }}>
            График работы: <b>ежедневно, 9:00–21:00</b>
          </div>
          <div style={{ marginTop: 10, borderRadius: 12, overflow: "hidden", border: "1px solid var(--line)" }}>
            <iframe title="План проезда" width="100%" height="240" frameBorder="0" loading="lazy"
              style={{ display: "block", border: 0 }}
              src={`https://yandex.ru/map-widget/v1/?mode=search&text=${encodeURIComponent(PICKUP_ADDRESS)}&z=17`} />
          </div>
          <a className="link" target="_blank" rel="noopener noreferrer"
            style={{ display: "inline-block", marginTop: 8 }}
            href={`https://yandex.ru/maps/?rtext=~${encodeURIComponent(PICKUP_ADDRESS)}&rtt=auto`}>
            Построить маршрут ↗
          </a>
        </div>
      )}
    </div>
  );
}
