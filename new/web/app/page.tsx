import SearchBox from "./SearchBox";

export default function Home() {
  return (
    <main className="container">
      <section className="hero">
        <h1>Запчасти для спецтехники.<br />Точно по артикулу.</h1>
        <p>Shantui, XCMG, SDLG, Doosan, Howo, Shacman, Weichai — в наличии и под заказ.</p>
        <SearchBox />
        <div className="hint">Введите артикул производителя, аналог или название — найдём за миллисекунды.</div>
        <div style={{ marginTop: 22 }}>
          <a href="/catalog" className="btn-secondary">Открыть каталог по маркам техники →</a>
        </div>
      </section>
    </main>
  );
}
