"use client";
import { useEffect, useState } from "react";

// Ссылки на боты/номера задаются через env (public). Пусто → показываем инструкцию без ссылки.
const TG_BOT = process.env.NEXT_PUBLIC_TG_BOT || "";       // напр. specinter_bot
const WA_NUMBER = process.env.NEXT_PUBLIC_WA_NUMBER || ""; // напр. 79024444342
const MAX_BOT = process.env.NEXT_PUBLIC_MAX_BOT || "";     // ссылка/ид бота MAX

export type Channel = "phone" | "telegram" | "whatsapp" | "max" | "instagram" | "email";

const CHANNELS: { key: Channel; label: string; placeholder: string }[] = [
  { key: "phone", label: "Телефон", placeholder: "+7 900 000-00-00" },
  { key: "telegram", label: "Telegram", placeholder: "@username или телефон" },
  { key: "whatsapp", label: "WhatsApp", placeholder: "телефон WhatsApp" },
  { key: "max", label: "MAX", placeholder: "@username или телефон" },
  { key: "instagram", label: "Instagram", placeholder: "@username в Instagram" },
  { key: "email", label: "Email", placeholder: "you@example.com" },
];

export default function ChannelPicker({
  onChange,
}: {
  onChange: (v: { channel: Channel; ref: string }) => void;
}) {
  const [channel, setChannel] = useState<Channel>("phone");
  const [ref, setRef] = useState("");

  useEffect(() => { onChange({ channel, ref }); }, [channel, ref, onChange]);

  const cur = CHANNELS.find((c) => c.key === channel)!;
  const hint = () => {
    if (channel === "telegram")
      return TG_BOT
        ? <>Нажмите <a className="link" target="_blank" rel="noopener noreferrer" href={`https://t.me/${TG_BOT}`}>открыть бота в Telegram</a> и «Start» — туда придёт подтверждение.</>
        : <>Укажите Telegram — с вами свяжется наш бот после запуска.</>;
    if (channel === "whatsapp")
      return WA_NUMBER
        ? <>Или напишите нам сами: <a className="link" target="_blank" rel="noopener noreferrer" href={`https://wa.me/${WA_NUMBER}`}>открыть WhatsApp</a>.</>
        : <>Ответим в WhatsApp на указанный номер.</>;
    if (channel === "max")
      return MAX_BOT
        ? <>Нажмите <a className="link" target="_blank" rel="noopener noreferrer" href={MAX_BOT}>открыть бота в MAX</a> и «Начать».</>
        : <>Свяжемся с вами в MAX.</>;
    if (channel === "instagram") return <>Напишем в Instagram Direct на указанный аккаунт.</>;
    if (channel === "email") return <>Пришлём подтверждение и счёт на почту.</>;
    return <>Перезвоним по указанному номеру.</>;
  };

  return (
    <div style={{ margin: "10px 0" }}>
      <div className="muted" style={{ fontSize: 13, marginBottom: 6 }}>Канал связи</div>
      <div className="chan-row">
        {CHANNELS.map((c) => (
          <button type="button" key={c.key}
            className={`chan-btn${channel === c.key ? " active" : ""}`}
            onClick={() => setChannel(c.key)}>{c.label}</button>
        ))}
      </div>
      <input className="fld" style={{ marginTop: 8 }} placeholder={cur.placeholder}
        value={ref} onChange={(e) => setRef(e.target.value)}
        type={channel === "email" ? "email" : "text"} required />
      <p className="muted" style={{ fontSize: 12, marginTop: 4 }}>{hint()}</p>
    </div>
  );
}
