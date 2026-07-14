import { apiMetrics } from "../../lib/api";

export const dynamic = "force-dynamic";

function fmt(n: number) {
  return (n ?? 0).toLocaleString("ru-RU");
}

function Tile({ label, value, sub }: { label: string; value: string; sub?: string }) {
  return (
    <div className="tile">
      <div className="tile-label">{label}</div>
      <div className="tile-value">{value}</div>
      {sub && <div className="tile-sub">{sub}</div>}
    </div>
  );
}

export default async function AdminPage() {
  const m = await apiMetrics();
  if (!m) return <main className="container"><div className="empty">Метрики недоступны.</div></main>;

  const maxQty = Math.max(1, ...m.top_products.map((p: any) => Number(p.qty)));

  return (
    <main className="container" style={{ paddingBottom: 64 }}>
      <h1 style={{ fontSize: 30, fontWeight: 600, letterSpacing: "-0.02em", margin: "32px 0 6px" }}>
        Панель метрик
      </h1>
      <p className="muted" style={{ marginBottom: 24 }}>Обзор портала СПЕЦИНТЕР</p>

      <div className="kpi">
        <Tile label="Выручка (оплачено)" value={`${fmt(Math.round(m.orders.revenue_rub))} ₽`}
              sub={`средний чек ${fmt(m.orders.avg_check_rub)} ₽`} />
        <Tile label="Заказы" value={fmt(m.orders.total)} sub={`оплачено ${fmt(m.orders.paid)}`} />
        <Tile label="Товары в каталоге" value={fmt(m.catalog.products)}
              sub={`${fmt(m.catalog.analogs)} аналогов`} />
        <Tile label="Предложения в наличии" value={fmt(m.offers.in_stock)}
              sub={`из ${fmt(m.offers.total)} всего`} />
        <Tile label="Поставщики" value={fmt(m.prices.suppliers)}
              sub={`${fmt(m.prices.price_rows)} строк прайса`} />
        <Tile label="Счета выставлены" value={fmt(m.documents.invoices)}
              sub={`ЭДО получено ${fmt(m.documents.edo_received)}`} />
      </div>

      {/* Покрытие матчинга прайсов — meter (одна шкала) */}
      <section className="panel">
        <h2 className="section">Покрытие матчинга прайсов</h2>
        <div className="meter"><div className="meter-fill" style={{ width: `${m.prices.match_coverage_pct}%` }} /></div>
        <div className="muted" style={{ marginTop: 8 }}>
          {m.prices.match_coverage_pct}% сопоставлено по артикулу · в очереди модерации: {fmt(m.prices.unmatched_queue)}
        </div>
      </section>

      {/* Топ товаров — горизонтальные бары (одна последовательная шкала) */}
      <section className="panel">
        <h2 className="section">Топ заказываемых позиций</h2>
        {m.top_products.length === 0 ? <p className="muted">Пока нет заказов.</p> :
          m.top_products.map((p: any, i: number) => (
            <div className="barrow" key={i}>
              <div className="barrow-label" title={p.name}>{p.article} · {p.name}</div>
              <div className="bartrack">
                <div className="bar" style={{ width: `${(Number(p.qty) / maxQty) * 100}%` }} />
              </div>
              <div className="barrow-val">{fmt(Number(p.qty))} шт</div>
            </div>
          ))}
      </section>

      {/* Последние заказы */}
      <section className="panel">
        <h2 className="section">Последние заказы</h2>
        <div className="tbl-scroll">
          <table className="tbl">
            <thead><tr><th>№</th><th>Клиент</th><th>Канал</th><th>Статус</th><th style={{ textAlign: "right" }}>Сумма</th></tr></thead>
            <tbody>
              {m.recent_orders.map((o: any) => (
                <tr key={o.id}>
                  <td>#{o.id}</td>
                  <td>{o.customer || "—"}</td>
                  <td>{o.channel}</td>
                  <td><span className={`badge ${o.status === "paid" ? "in" : "out"}`}>{o.status}</span></td>
                  <td style={{ textAlign: "right" }}>{fmt(Math.round(o.total))} ₽</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </main>
  );
}
