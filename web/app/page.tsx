import { redirect } from "next/navigation";

// Главная страница — сразу каталог.
export default function Home() {
  redirect("/catalog");
}
