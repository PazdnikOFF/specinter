import Link from "next/link";

export default function OrderSuccess() {
  return (
    <main className="container" style={{ maxWidth: 640 }}>
      <div className="panel" style={{ marginTop: 60, textAlign: "center" }}>
        <div style={{ fontSize: 48 }}>✅</div>
        <h1 style={{ fontSize: 26, fontWeight: 600, margin: "12px 0" }}>Спасибо за заказ!</h1>
        <p className="muted">
          Оплата принята. Менеджер свяжется с вами для подтверждения отгрузки.
          Документы придут на указанные контакты.
        </p>
        <p style={{ marginTop: 20 }}><Link className="btn-primary" href="/catalog">Вернуться в каталог</Link></p>
      </div>
    </main>
  );
}
