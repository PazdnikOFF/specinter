import { redirect } from "next/navigation";

// Не статизировать: нужен честный серверный 307 с Location (health-check/SEO),
// иначе Next при сборке превращает redirect() в клиентский (307 с HTML-телом).
export const dynamic = "force-dynamic";

// Главная страница — сразу каталог.
export default function Home() {
  redirect("/catalog");
}
