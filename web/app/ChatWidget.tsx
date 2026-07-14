"use client";
import { useState } from "react";
import { usePathname } from "next/navigation";

// Клиентский ИИ-консультант витрины. Строгие рамки задаются на бэкенде
// (только продажа запчастей, без раскрытия поставщиков/закупки).
export default function ChatWidget() {
  const path = usePathname();
  const [open, setOpen] = useState(false);
  const [msgs, setMsgs] = useState<{ role: string; content: string }[]>([]);
  const [input, setInput] = useState("");
  const [busy, setBusy] = useState(false);

  async function send() {
    const text = input.trim();
    if (!text || busy) return;
    const next = [...msgs, { role: "user", content: text }];
    setMsgs(next); setInput(""); setBusy(true);
    try {
      const r = await fetch("/api/assistant/chat", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ messages: next }),
      });
      const d = await r.json();
      setMsgs([...next, { role: "assistant", content: d.reply || "Извините, сейчас недоступно." }]);
    } catch {
      setMsgs([...next, { role: "assistant", content: "Связь прервалась, попробуйте ещё раз." }]);
    } finally { setBusy(false); }
  }

  if (path?.startsWith("/admin")) return null;   // в админке свой ассистент

  return (
    <>
      <button className="cw-fab" aria-label="Чат с консультантом" onClick={() => setOpen((o) => !o)}>
        {open ? "×" : "💬"}
      </button>
      {open && (
        <div className="cw-panel">
          <div className="cw-head">
            <b>Подбор запчастей</b>
            <button className="pg" onClick={() => setOpen(false)}>×</button>
          </div>
          <div className="cw-body">
            {msgs.length === 0 && (
              <div className="chat-msg assistant"><span>Здравствуйте! Назовите артикул или деталь — подскажу цену, наличие и срок.</span></div>
            )}
            {msgs.map((m, i) => (
              <div key={i} className={`chat-msg ${m.role}`}><span>{m.content}</span></div>
            ))}
            {busy && <div className="chat-msg assistant"><span className="muted">…</span></div>}
          </div>
          <div className="cw-foot">
            <input className="fld" style={{ margin: 0, flex: 1 }} placeholder="Артикул или вопрос…"
              value={input} onChange={(e) => setInput(e.target.value)}
              onKeyDown={(e) => { if (e.key === "Enter") send(); }} />
            <button className="btn-primary" onClick={send} disabled={busy}>➤</button>
          </div>
        </div>
      )}
    </>
  );
}
