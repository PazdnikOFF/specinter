export const metadata = { title: "О нас — СПЕЦИНТЕР" };

export default function ONas() {
  return (
    <main className="container" style={{ maxWidth: 820, paddingBottom: 64 }}>
      <h1 className="cat-title" style={{ marginTop: 28 }}>О компании</h1>

      <section className="panel">
        <p><b>ООО «СПЕЦИНТЕР»</b> — поставщик запасных частей для китайской спецтехники и грузовых
          автомобилей. Работаем с техникой Shantui, XCMG, SDLG, Doosan, Howo, Shacman, Weichai и другими
          марками — от бульдозеров и погрузчиков до экскаваторов и самосвалов.</p>
        <p>Мы помогаем подобрать деталь <b>точно по артикулу производителя</b>, по аналогам и кросс‑номерам,
          а также по каталожным схемам узлов — с указанием позиции детали на схеме. Часть позиций держим
          в наличии на складе, остальное привозим под заказ от проверенных поставщиков.</p>
      </section>

      <section className="panel">
        <h2 className="section" style={{ marginTop: 0 }}>Почему мы</h2>
        <ul style={{ margin: 0, paddingLeft: 20, lineHeight: 1.7 }}>
          <li>Точный подбор по артикулу, аналогам и схемам узлов.</li>
          <li>Актуальные цены и наличие, автоматический расчёт срока и стоимости доставки.</li>
          <li>Работа с физическими и юридическими лицами: онлайн‑оплата и счета с НДС.</li>
          <li>Самовывоз со склада в Екатеринбурге и доставка по всей России.</li>
          <li>Быстрая связь в удобном канале: телефон, Telegram, WhatsApp, MAX, e‑mail.</li>
        </ul>
      </section>

      <section className="panel">
        <h2 className="section" style={{ marginTop: 0 }}>Контакты</h2>
        <p>Екатеринбург, пер. Шофёров, 11 · склад работает ежедневно 9:00–21:00<br />
          Телефон: <a className="link" href="tel:+73434547788">+7 (343) 454‑77‑88</a><br />
          E‑mail: <a className="link" href="mailto:info@specinter.ru">info@specinter.ru</a></p>
      </section>
    </main>
  );
}
