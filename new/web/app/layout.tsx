import "./globals.css";
import Link from "next/link";
import type { Metadata } from "next";
import CartLink from "./CartLink";
import ThemeToggle from "./ThemeToggle";
import ChatWidget from "./ChatWidget";
import SocialLinks from "./SocialLinks";

export const metadata: Metadata = {
  title: "СПЕЦИНТЕР — запчасти для китайской спецтехники",
  description: "Запчасти для бульдозеров, погрузчиков, экскаваторов и грузовиков. Подбор по артикулу и аналогам.",
};

const themeInit = `try{document.documentElement.dataset.theme=localStorage.getItem('theme')||'light';}catch(e){document.documentElement.dataset.theme='light';}`;

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="ru" suppressHydrationWarning>
      <head><script dangerouslySetInnerHTML={{ __html: themeInit }} /></head>
      <body>
        <header className="nav">
          <div className="container nav-inner">
            <Link href="/" className="brand">СПЕЦИНТЕР</Link>
            <Link href="/catalog" className="link">Каталог</Link>
            <Link href="/oplata" className="link">Оплата и доставка</Link>
            <Link href="/o-nas" className="link">О нас</Link>
            <CartLink />
            <span style={{ flex: 1 }} />
            <a href="tel:+73434547788" className="link">+7 (343) 454-77-88</a>
            <ThemeToggle />
          </div>
        </header>
        {children}
        <ChatWidget />
        <footer>
          <div className="container">
            ООО «СПЕЦИНТЕР» · Екатеринбург, пер. Шофёров 11 · info@specinter.ru
            <SocialLinks />
          </div>
        </footer>
      </body>
    </html>
  );
}
