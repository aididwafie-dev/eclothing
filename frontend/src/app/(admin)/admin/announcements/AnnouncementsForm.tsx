"use client";

import { useState } from "react";
import type { AnnouncementAudience } from "@/lib/types";

const AUDIENCES: { value: AnnouncementAudience; label: string }[] = [
  { value: "all", label: "All active members" },
  { value: "unconfirmed", label: "Unconfirmed email" },
  { value: "confirmed", label: "Confirmed email" },
  { value: "no_orders", label: "No orders placed" },
];

export default function AnnouncementsForm({
  audienceCounts,
}: {
  audienceCounts: Record<AnnouncementAudience, number>;
}) {
  const [from, setFrom] = useState("no-reply@rmafplas.com");
  const [subject, setSubject] = useState("");
  const [body, setBody] = useState("");
  const [sendTo, setSendTo] = useState<AnnouncementAudience>("all");
  const [sending, setSending] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const count = audienceCounts[sendTo] ?? 0;
    if (!confirm(`Send this email to ${count} member(s) now? This cannot be undone.`)) {
      return;
    }

    setSending(true);
    setMessage(null);
    try {
      const res = await fetch("/api/admin/announcements/send", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ from, subject, body, send_to: sendTo }),
      });
      const data = await res.json();
      if (!res.ok) {
        setMessage({ kind: "error", text: data.message ?? "Could not send the announcement." });
        return;
      }
      setMessage({ kind: "ok", text: data.message });
      setSubject("");
      setBody("");
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setSending(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="rounded-lg border border-black/10 bg-white p-6">
      <h1 className="text-xl font-semibold">Announcements</h1>
      <p className="mt-1 text-sm text-red-600">
        Sends immediately by email, synchronously, to every matching member. There is no undo.
      </p>

      <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label className="block text-sm font-medium">From</label>
          <input
            type="email"
            required
            value={from}
            onChange={(e) => setFrom(e.target.value)}
            className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium">Send to</label>
          <select
            value={sendTo}
            onChange={(e) => setSendTo(e.target.value as AnnouncementAudience)}
            className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
          >
            {AUDIENCES.map((a) => (
              <option key={a.value} value={a.value}>
                {a.label} ({audienceCounts[a.value] ?? 0})
              </option>
            ))}
          </select>
        </div>
        <div className="col-span-full">
          <label className="block text-sm font-medium">Subject</label>
          <input
            type="text"
            required
            maxLength={255}
            value={subject}
            onChange={(e) => setSubject(e.target.value)}
            className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
        <div className="col-span-full">
          <label className="block text-sm font-medium">Body</label>
          <textarea
            required
            rows={8}
            value={body}
            onChange={(e) => setBody(e.target.value)}
            className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
      </div>

      <div className="mt-4 flex items-center gap-4">
        <button
          type="submit"
          disabled={sending}
          className="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
        >
          {sending ? "Sending..." : "Send"}
        </button>
        {message && (
          <span className={message.kind === "ok" ? "text-sm text-green-700" : "text-sm text-red-600"}>
            {message.text}
          </span>
        )}
      </div>
    </form>
  );
}
