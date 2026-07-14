"use client";
import { useCallback, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { adminFetch, adminJson } from "../../lib/admin";
import { imgUrl, thumbUrl } from "../../lib/api";

type Tab = "price" | "supplier_prices" | "suppliers" | "integrations" | "assistant" | "metrics";
const TABS: [Tab, string][] = [
  ["price", "Мой прайс / каталог"],
  ["supplier_prices", "Прайсы поставщиков"],
  ["suppliers", "Поставщики"],
  ["integrations", "Интеграции"],
  ["assistant", "ИИ-ассистент"],
  ["metrics", "Метрики"],
];

export default function AdminPage() {
  const router = useRouter();
  const [ok, setOk] = useState<boolean | null>(null);
  const [tab, setTab] = useState<Tab>("price");

  useEffect(() => {
    let alive = true;
    (async () => {
      try {
        const r = await adminFetch("/api/admin/me");
        if (!alive) return;
        if (r.ok) setOk(true);
        else router.replace("/admin/login");
      } catch {
        if (alive) router.replace("/admin/login");   // сбой сети/CORS — на страницу входа
      }
    })();
    return () => { alive = false; };
  }, [router]);

  if (ok === null) return <main className="container"><p className="muted" style={{ padding: 40 }}>Загрузка…</p></main>;

  return (
    <main className="container" style={{ paddingBottom: 64 }}>
      <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", margin: "28px 0 8px" }}>
        <h1 style={{ fontSize: 28, fontWeight: 600, letterSpacing: "-0.02em" }}>Админка СПЕЦИНТЕР</h1>
        <button className="btn-secondary" onClick={async () => {
          await adminFetch("/api/admin/logout", { method: "POST" });
          router.replace("/admin/login");
        }}>Выйти</button>
      </div>
      <div className="admtabs">
        {TABS.map(([k, label]) => (
          <button key={k} className={`admtab${tab === k ? " active" : ""}`} onClick={() => setTab(k)}>{label}</button>
        ))}
      </div>
      {tab === "price" && <MyPrice />}
      {tab === "supplier_prices" && <SupplierPrices />}
      {tab === "suppliers" && <Suppliers />}
      {tab === "integrations" && <Integrations />}
      {tab === "assistant" && <Assistant />}
      {tab === "metrics" && <Metrics />}
    </main>
  );
}

function Pager({ page, total, per, onGo }: { page: number; total: number; per: number; onGo: (p: number) => void }) {
  const pages = Math.max(1, Math.ceil(total / per));
  if (pages <= 1) return null;
  return (
    <div className="pager">
      {page > 1 && <button className="pg" onClick={() => onGo(page - 1)}>← Назад</button>}
      <span className="pg-info">Стр. {page} из {pages} · всего {total.toLocaleString("ru-RU")}</span>
      {page < pages && <button className="pg" onClick={() => onGo(page + 1)}>Вперёд →</button>}
    </div>
  );
}

/* ---- Мой прайс / каталог (итоговые цены + правка карточек) ---- */
function MyPrice() {
  const [q, setQ] = useState("");
  const [onlyPriced, setOnlyPriced] = useState(true);
  const [data, setData] = useState<any>({ items: [], total: 0 });
  const [page, setPage] = useState(1);
  const [imgFor, setImgFor] = useState<any>(null);   // товар для менеджера фото
  const per = 50;

  const load = useCallback(async () => {
    const d = await adminJson(`/api/admin/my-price?page=${page}&per_page=${per}&only_priced=${onlyPriced}` +
      (q ? `&q=${encodeURIComponent(q)}` : ""));
    if (d) setData(d);
  }, [q, onlyPriced, page]);
  useEffect(() => { load(); }, [load]);

  async function save(id: number, body: any) {
    await adminFetch(`/api/admin/products/${id}`, { method: "PATCH", body: JSON.stringify(body) });
    load();
  }

  return (
    <section>
      <div className="admbar">
        <input className="fld" style={{ maxWidth: 360, margin: 0 }} placeholder="Поиск по артикулу/названию…"
          value={q} onChange={(e) => { setPage(1); setQ(e.target.value); }} />
        <label className="muted" style={{ fontSize: 13 }}>
          <input type="checkbox" checked={onlyPriced} onChange={(e) => { setPage(1); setOnlyPriced(e.target.checked); }} /> только с ценой
        </label>
      </div>
      <div className="tbl-scroll">
        <table className="tbl">
          <thead><tr><th>Артикул</th><th>Название</th><th>Цена</th><th>Пост.</th><th>Наличие</th><th>Видим.</th><th>Фото</th></tr></thead>
          <tbody>
            {data.items.map((r: any) => (
              <tr key={r.id}>
                <td style={{ fontWeight: 600 }}>{r.manufacturer_article}</td>
                <td><EditableName value={r.name} onSave={(v) => save(r.id, { name: v })} /></td>
                <td>{r.sell_price != null ? `${Math.round(r.sell_price).toLocaleString("ru-RU")} ₽` : "—"}</td>
                <td>{r.suppliers || 0}</td>
                <td><span className={`badge ${r.in_stock ? "in" : "out"}`}>{r.in_stock ? "есть" : "нет"}</span></td>
                <td><input type="checkbox" checked={!!r.visible} onChange={(e) => save(r.id, { visible: e.target.checked })} /></td>
                <td><button className="pg" style={{ padding: "4px 10px" }} onClick={() => setImgFor(r)}>Фото</button></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Pager page={page} total={data.total} per={per} onGo={setPage} />
      {imgFor && <ImageManager product={imgFor} onClose={() => setImgFor(null)} />}
    </section>
  );
}

/* ---- Менеджер фото товара (загрузка/галерея/главное + поиск по артикулу) ---- */
function ImageManager({ product, onClose }: { product: any; onClose: () => void }) {
  const [d, setD] = useState<any>(null);
  const [busy, setBusy] = useState(false);
  const [url, setUrl] = useState("");
  const [msg, setMsg] = useState("");
  const id = product.id;
  const art = product.manufacturer_article || "";

  const load = useCallback(async () => {
    setD(await adminJson(`/api/admin/products/${id}/images`));
  }, [id]);
  useEffect(() => { load(); }, [load]);

  async function upload(file: File, primary: boolean) {
    setBusy(true);
    const fd = new FormData();
    fd.append("file", file);
    await adminFetch(`/api/admin/products/${id}/images?primary=${primary}`, { method: "POST", body: fd });
    setBusy(false);
    load();
  }
  async function addByUrl(primary: boolean) {
    if (!url.trim()) return;
    setBusy(true); setMsg("Скачиваю…");
    const r = await adminFetch(`/api/admin/products/${id}/image-from-url`, {
      method: "POST", body: JSON.stringify({ url: url.trim(), primary }),
    });
    const j = await r.json().catch(() => null);
    setBusy(false);
    setMsg(r.ok ? "Добавлено — проверьте превью на водяной знак" : `Ошибка: ${j?.detail || r.status}`);
    if (r.ok) { setUrl(""); load(); }
  }
  async function setPrimary(url: string) {
    await adminFetch(`/api/admin/products/${id}/set-primary`, { method: "POST", body: JSON.stringify({ url }) });
    load();
  }
  async function del(url: string) {
    await adminFetch(`/api/admin/products/${id}/images?url=${encodeURIComponent(url)}`, { method: "DELETE" });
    load();
  }

  return (
    <div className="modal-bg" onClick={onClose}>
      <div className="modal" onClick={(e) => e.stopPropagation()}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
          <h2 className="section" style={{ margin: 0 }}>Фото · {art}</h2>
          <button className="pg" onClick={onClose}>✕</button>
        </div>
        {!d ? <p className="muted">Загрузка…</p> : (
          <>
            <div style={{ display: "flex", gap: 14, flexWrap: "wrap", margin: "14px 0" }}>
              <div className="img-slot">
                {d.primary_image ? <img src={thumbUrl(d.primary_image)!} alt="" /> : <span className="thumb-ph">нет главного</span>}
                <span className="img-cap">главное</span>
              </div>
              {d.gallery.map((g: any) => (
                <div className="img-slot" key={g.url}>
                  <img src={thumbUrl(g.url)!} alt="" />
                  <span className="img-actions">
                    <button title="Сделать главным" onClick={() => setPrimary(g.url)}>★</button>
                    <button title="Удалить" onClick={() => del(g.url)}>🗑</button>
                  </span>
                </div>
              ))}
            </div>
            <div className="admbar">
              <label className="btn-secondary" style={{ cursor: "pointer" }}>
                {busy ? "Загрузка…" : "＋ Главное фото"}
                <input type="file" accept="image/*" style={{ display: "none" }}
                  onChange={(e) => { const f = e.target.files?.[0]; if (f) upload(f, true); e.currentTarget.value = ""; }} />
              </label>
              <label className="btn-secondary" style={{ cursor: "pointer" }}>
                ＋ В галерею
                <input type="file" accept="image/*" style={{ display: "none" }}
                  onChange={(e) => { const f = e.target.files?.[0]; if (f) upload(f, false); e.currentTarget.value = ""; }} />
              </label>
              <a className="btn-secondary" target="_blank" rel="noopener noreferrer"
                href={`https://yandex.ru/images/search?text=${encodeURIComponent(art)}`}>
                Искать фото по артикулу ↗
              </a>
            </div>
            <div className="admbar" style={{ marginTop: 4 }}>
              <input className="fld" style={{ margin: 0, maxWidth: 380 }} placeholder="Вставьте прямую ссылку на изображение…"
                value={url} onChange={(e) => setUrl(e.target.value)} />
              <button className="btn-secondary" disabled={busy} onClick={() => addByUrl(true)}>Как главное</button>
              <button className="btn-secondary" disabled={busy} onClick={() => addByUrl(false)}>В галерею</button>
            </div>
            {msg && <p className="muted" style={{ fontSize: 12 }}>{msg}</p>}
            <p className="muted" style={{ fontSize: 12 }}>
              Поиск по артикулу <b>производителя</b> ({art}). Проверяйте превью: изображения
              с чужим <b>водяным знаком</b> не сохраняйте (удалите 🗑). Ответственность за
              источник — на стороне пользователя.
            </p>
          </>
        )}
      </div>
    </div>
  );
}

function EditableName({ value, onSave }: { value: string; onSave: (v: string) => void }) {
  const [edit, setEdit] = useState(false);
  const [v, setV] = useState(value || "");
  if (!edit) return <span onClick={() => { setV(value || ""); setEdit(true); }} style={{ cursor: "text" }}>{value || <span className="muted">—</span>}</span>;
  return (
    <input className="fld" style={{ margin: 0, padding: "6px 8px" }} autoFocus value={v}
      onChange={(e) => setV(e.target.value)}
      onBlur={() => { setEdit(false); if (v !== value) onSave(v); }}
      onKeyDown={(e) => { if (e.key === "Enter") (e.target as HTMLInputElement).blur(); if (e.key === "Escape") setEdit(false); }} />
  );
}

/* ---- Сырьё прайсов поставщиков ---- */
function SupplierPrices() {
  const [suppliers, setSuppliers] = useState<any[]>([]);
  const [sid, setSid] = useState("");
  const [q, setQ] = useState("");
  const [matched, setMatched] = useState("");
  const [data, setData] = useState<any>({ items: [], total: 0 });
  const [page, setPage] = useState(1);
  const per = 50;

  useEffect(() => { adminJson("/api/admin/suppliers").then((d) => d && setSuppliers(d)); }, []);
  const load = useCallback(async () => {
    const d = await adminJson(`/api/admin/supplier-prices?page=${page}&per_page=${per}` +
      (sid ? `&supplier_id=${sid}` : "") + (q ? `&q=${encodeURIComponent(q)}` : "") +
      (matched ? `&matched=${matched}` : ""));
    if (d) setData(d);
  }, [sid, q, matched, page]);
  useEffect(() => { load(); }, [load]);

  return (
    <section>
      <div className="admbar">
        <select className="fld" style={{ maxWidth: 220, margin: 0 }} value={sid} onChange={(e) => { setPage(1); setSid(e.target.value); }}>
          <option value="">Все поставщики</option>
          {suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
        </select>
        <select className="fld" style={{ maxWidth: 180, margin: 0 }} value={matched} onChange={(e) => { setPage(1); setMatched(e.target.value); }}>
          <option value="">Все</option>
          <option value="matched">Сопоставленные</option>
          <option value="unmatched">Не найдено</option>
        </select>
        <input className="fld" style={{ maxWidth: 300, margin: 0 }} placeholder="Поиск…"
          value={q} onChange={(e) => { setPage(1); setQ(e.target.value); }} />
      </div>
      <div className="tbl-scroll">
        <table className="tbl">
          <thead><tr><th>Поставщик</th><th>Артикул</th><th>Название</th><th>Цена</th><th>Кол-во</th><th>Матчинг</th></tr></thead>
          <tbody>
            {data.items.map((r: any) => (
              <tr key={r.id}>
                <td>{r.supplier}</td>
                <td style={{ fontWeight: 600 }}>{r.article}</td>
                <td>{r.name}</td>
                <td>{r.price != null ? `${Math.round(r.price).toLocaleString("ru-RU")} ₽` : "—"}</td>
                <td>{r.qty ?? "—"}</td>
                <td>{r.matched_product_id
                  ? <span className="badge in">{r.match_method}</span>
                  : <span className="badge out">нет</span>}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Pager page={page} total={data.total} per={per} onGo={setPage} />
    </section>
  );
}

/* ---- Поставщики: карточки, срок поставки, загрузка прайса ---- */
function Suppliers() {
  const [list, setList] = useState<any[]>([]);
  const [msg, setMsg] = useState("");
  const load = useCallback(async () => { const d = await adminJson("/api/admin/suppliers"); if (d) setList(d); }, []);
  useEffect(() => { load(); }, [load]);

  async function save(id: number, body: any) {
    await adminFetch(`/api/admin/suppliers/${id}`, { method: "PATCH", body: JSON.stringify(body) });
    load();
  }
  async function addSupplier() {
    const name = prompt("Название поставщика");
    if (!name) return;
    await adminFetch(`/api/admin/suppliers?name=${encodeURIComponent(name)}`, { method: "POST" });
    load();
  }
  async function upload(id: number, file: File) {
    setMsg(`Загрузка «${file.name}»…`);
    const fd = new FormData();
    fd.append("file", file);
    const r = await adminFetch(`/api/admin/prices/upload?supplier_id=${id}`, { method: "POST", body: fd });
    const d = await r.json().catch(() => null);
    setMsg(r.ok && d ? `«${file.name}»: строк ${d.rows}, матч ${JSON.stringify(d.match)}` : `Ошибка загрузки «${file.name}»`);
    load();
  }

  return (
    <section>
      <div className="admbar">
        <button className="btn-primary" onClick={addSupplier}>+ Поставщик</button>
        {msg && <span className="muted" style={{ fontSize: 13 }}>{msg}</span>}
      </div>
      <div style={{ display: "grid", gap: 12 }}>
        {list.map((s) => (
          <div className="panel" key={s.id} style={{ marginTop: 0 }}>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(180px,1fr))", gap: 10 }}>
              <Field label="Название" value={s.name} onSave={(v) => save(s.id, { name: v })} />
              <Field label="E-mail отправителя (Яндекс)" value={s.sender_email || ""} onSave={(v) => save(s.id, { sender_email: v })} />
              <Field label="Город" value={s.city || ""} onSave={(v) => save(s.id, { city: v })} />
              <Field label="Срок поставки, раб. дн." value={s.delivery_days ?? ""} type="number"
                onSave={(v) => save(s.id, { delivery_days: v === "" ? null : Number(v) })} />
              <Field label="Пометка к сроку" value={s.delivery_note || ""} onSave={(v) => save(s.id, { delivery_note: v })} />
              <Field label="Наценка, %" value={s.markup_percent} type="number"
                onSave={(v) => save(s.id, { markup_percent: Number(v) })} />
            </div>
            <div style={{ marginTop: 10 }}>
              <label className="btn-secondary" style={{ cursor: "pointer" }}>
                Загрузить прайс (.xls/.xlsx)
                <input type="file" accept=".xls,.xlsx" style={{ display: "none" }}
                  onChange={(e) => { const f = e.target.files?.[0]; if (f) upload(s.id, f); e.currentTarget.value = ""; }} />
              </label>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}

function Field({ label, value, onSave, type = "text" }: { label: string; value: any; onSave: (v: string) => void; type?: string }) {
  const [v, setV] = useState(String(value ?? ""));
  useEffect(() => { setV(String(value ?? "")); }, [value]);
  return (
    <label style={{ display: "block" }}>
      <span className="muted" style={{ fontSize: 12 }}>{label}</span>
      <input className="fld" type={type} style={{ margin: "4px 0 0" }} value={v}
        onChange={(e) => setV(e.target.value)}
        onBlur={() => { if (v !== String(value ?? "")) onSave(v); }}
        onKeyDown={(e) => { if (e.key === "Enter") (e.target as HTMLInputElement).blur(); }} />
    </label>
  );
}

/* ---- Интеграции: токены DaData / ДЛ / мессенджеров / LLM ---- */
function Integrations() {
  const [list, setList] = useState<any[]>([]);
  const [vals, setVals] = useState<Record<string, string>>({});
  const [msg, setMsg] = useState("");
  const load = useCallback(async () => {
    const d = await adminJson("/api/admin/settings");
    if (d) setList(d.settings);
  }, []);
  useEffect(() => { load(); }, [load]);

  async function save(key: string) {
    setMsg("");
    const r = await adminFetch("/api/admin/settings", {
      method: "PUT", body: JSON.stringify({ key, value: vals[key] ?? "" }),
    });
    if (r.ok) { setMsg(`${key} сохранён`); setVals((v) => ({ ...v, [key]: "" })); load(); }
  }

  return (
    <section>
      <p className="muted" style={{ marginTop: 0 }}>
        Ключи интеграций хранятся в БД — задаются здесь без правки конфигов. Пустое значение = режим dry-run (заглушка).
        Эти же настройки может менять ИИ-ассистент по вашей команде.
      </p>
      <div style={{ display: "grid", gap: 10 }}>
        {list.map((s) => (
          <div className="panel" key={s.key} style={{ marginTop: 0, padding: 14 }}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", gap: 10 }}>
              <div>
                <b>{s.key}</b> <span className={`badge ${s.configured ? "in" : "out"}`}>{s.configured ? "настроено" : "не задано"}</span>
                <div className="muted" style={{ fontSize: 12 }}>{s.description}{s.preview ? ` · ${s.preview}` : ""}</div>
              </div>
            </div>
            <div className="admbar" style={{ marginTop: 8, marginBottom: 0 }}>
              <input className="fld" style={{ margin: 0, maxWidth: 360 }} placeholder="новое значение…"
                type={s.secret ? "password" : "text"}
                value={vals[s.key] ?? ""} onChange={(e) => setVals((v) => ({ ...v, [s.key]: e.target.value }))} />
              <button className="btn-secondary" onClick={() => save(s.key)}>Сохранить</button>
            </div>
          </div>
        ))}
      </div>
      {msg && <p className="muted" style={{ marginTop: 10 }}>{msg}</p>}
    </section>
  );
}

/* ---- ИИ-ассистент админки (полный администратор портала) ---- */
function Assistant() {
  const [msgs, setMsgs] = useState<{ role: string; content: string }[]>([]);
  const [input, setInput] = useState("");
  const [busy, setBusy] = useState(false);

  async function send() {
    const text = input.trim();
    if (!text || busy) return;
    const next = [...msgs, { role: "user", content: text }];
    setMsgs(next); setInput(""); setBusy(true);
    const r = await adminFetch("/api/admin/assistant", { method: "POST", body: JSON.stringify({ messages: next }) });
    const d = await r.json().catch(() => null);
    setBusy(false);
    setMsgs([...next, { role: "assistant", content: d?.reply || "Ошибка ответа" }]);
  }

  return (
    <section>
      <p className="muted" style={{ marginTop: 0 }}>
        ИИ-администратор портала: настраивает интеграции, управляет поставщиками/ценами/каталогом,
        обрабатывает заявки. Пример: «подключи DaData, токен abc123», «поставщику 5 срок 4 дня и наценку 20%»,
        «обработай новые заявки», «переиндексируй каталог».
      </p>
      <div className="chat-log">
        {msgs.length === 0 && <p className="muted" style={{ padding: 12 }}>Задайте команду администратору портала…</p>}
        {msgs.map((m, i) => (
          <div key={i} className={`chat-msg ${m.role}`}><span>{m.content}</span></div>
        ))}
        {busy && <div className="chat-msg assistant"><span className="muted">…</span></div>}
      </div>
      <div className="admbar" style={{ marginTop: 10 }}>
        <input className="fld" style={{ margin: 0, flex: 1 }} placeholder="Команда администратору…"
          value={input} onChange={(e) => setInput(e.target.value)}
          onKeyDown={(e) => { if (e.key === "Enter") send(); }} />
        <button className="btn-primary" onClick={send} disabled={busy}>Отправить</button>
      </div>
    </section>
  );
}

/* ---- Метрики (кратко) ---- */
function Metrics() {
  const [m, setM] = useState<any>(null);
  useEffect(() => { adminJson("/api/admin/metrics").then(setM); }, []);
  if (!m) return <p className="muted" style={{ padding: 20 }}>Загрузка метрик…</p>;
  const f = (n: number) => (n ?? 0).toLocaleString("ru-RU");
  return (
    <section className="kpi">
      <Tile label="Товары в каталоге" value={f(m.catalog.products)} sub={`${f(m.catalog.analogs)} аналогов`} />
      <Tile label="Предложения в наличии" value={f(m.offers.in_stock)} sub={`из ${f(m.offers.total)}`} />
      <Tile label="Поставщики" value={f(m.prices.suppliers)} sub={`${f(m.prices.price_rows)} строк прайса`} />
      <Tile label="Покрытие матчинга" value={`${m.prices.match_coverage_pct}%`} sub={`в модерации ${f(m.prices.unmatched_queue)}`} />
      <Tile label="Заказы" value={f(m.orders.total)} sub={`оплачено ${f(m.orders.paid)}`} />
      <Tile label="Выручка" value={`${f(Math.round(m.orders.revenue_rub))} ₽`} />
    </section>
  );
}
function Tile({ label, value, sub }: { label: string; value: string; sub?: string }) {
  return <div className="tile"><div className="tile-label">{label}</div><div className="tile-value">{value}</div>{sub && <div className="tile-sub">{sub}</div>}</div>;
}
