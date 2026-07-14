"use client";
import { useRouter } from "next/navigation";

// Кнопка возврата назад (по истории браузера; если её нет — в каталог).
export default function BackButton({ fallback = "/catalog" }: { fallback?: string }) {
  const router = useRouter();
  return (
    <button className="back-btn" type="button" onClick={() => {
      if (typeof window !== "undefined" && window.history.length > 1) router.back();
      else router.push(fallback);
    }}>
      <span aria-hidden="true">‹</span> Назад
    </button>
  );
}
