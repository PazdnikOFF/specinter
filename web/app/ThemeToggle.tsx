"use client";
import { useEffect, useState } from "react";

export default function ThemeToggle() {
  const [theme, setTheme] = useState<"light" | "dark">("light");

  useEffect(() => {
    setTheme((document.documentElement.dataset.theme as "light" | "dark") || "light");
  }, []);

  const toggle = () => {
    const next = theme === "dark" ? "light" : "dark";
    setTheme(next);
    document.documentElement.dataset.theme = next;
    try { localStorage.setItem("theme", next); } catch {}
  };

  return (
    <button className="theme-toggle" onClick={toggle} aria-label="Переключить тему"
      title={theme === "dark" ? "Светлая тема" : "Тёмная тема"}>
      {theme === "dark" ? "☀" : "☾"}
    </button>
  );
}
