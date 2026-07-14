"use client";
import { useEffect, useState } from "react";

// Ссылки на соцсети (Instagram, YouTube) — задаются в админке → Интеграции.
export default function SocialLinks() {
  const [cfg, setCfg] = useState<{ instagram_url?: string; youtube_url?: string }>({});
  useEffect(() => {
    fetch("/api/site-config").then((r) => r.json()).then(setCfg).catch(() => {});
  }, []);
  if (!cfg.instagram_url && !cfg.youtube_url) return null;
  return (
    <span style={{ display: "inline-flex", gap: 14, marginLeft: 12 }}>
      {cfg.instagram_url && (
        <a className="link" href={cfg.instagram_url} target="_blank" rel="noopener noreferrer" title="Instagram">Instagram</a>
      )}
      {cfg.youtube_url && (
        <a className="link" href={cfg.youtube_url} target="_blank" rel="noopener noreferrer" title="YouTube">YouTube</a>
      )}
    </span>
  );
}
