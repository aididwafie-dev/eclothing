"use client";

import { useState } from "react";
import { apiPost, apiPut } from "@/lib/clientApi";
import type { NotificationSettingsResponse } from "@/lib/types";

export default function NotificationsTab({ initialData }: { initialData: NotificationSettingsResponse }) {
  const [enabled, setEnabled] = useState(initialData.pushEnabled);
  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");
  const [sending, setSending] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  async function toggleEnabled() {
    const next = !enabled;
    setEnabled(next);
    await apiPut("/api/admin/system-settings/notifications", { enabled: next });
  }

  async function handleSend(e: React.FormEvent) {
    e.preventDefault();
    if (!confirm(`Send this announcement to ${initialData.activeUserCount} active member(s) now?`)) return;

    setSending(true);
    setMessage(null);
    try {
      const res = await apiPost("/api/admin/system-settings/notifications/announce", { title, body });
      const data = await res.json();
      setMessage({ kind: data.ok ? "ok" : "error", text: data.message ?? "Could not send." });
      if (data.ok) {
        setTitle("");
        setBody("");
      }
    } finally {
      setSending(false);
    }
  }

  return (
    <div className="flex flex-col gap-8">
      <div className="rounded-md border border-black/10 p-4">
        <label className="flex items-center gap-2 text-sm font-medium">
          <input type="checkbox" checked={enabled} onChange={toggleEnabled} />
          Push notifications enabled
        </label>
        <p className="mt-1 text-sm text-black/50">
          FCM is {initialData.fcmConfigured ? "configured" : "not configured"} (project credentials come from
          environment variables). {initialData.activeUserCount} active member(s), {initialData.deviceCount}{" "}
          registered device(s).
        </p>
      </div>

      <form onSubmit={handleSend} className="rounded-md border border-black/10 p-4">
        <h2 className="font-semibold">Send Announcement</h2>
        <p className="mt-1 text-sm text-black/50">
          Recorded in every active member&apos;s in-app inbox, then pushed to their devices if push is enabled.
        </p>
        <div className="mt-3 flex flex-col gap-3">
          <div>
            <label className="block text-sm font-medium">Title</label>
            <input
              type="text"
              required
              maxLength={255}
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              className="mt-1 w-full max-w-md rounded-md border border-black/15 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-medium">Body</label>
            <textarea
              required
              rows={4}
              maxLength={1000}
              value={body}
              onChange={(e) => setBody(e.target.value)}
              className="mt-1 w-full max-w-md rounded-md border border-black/15 px-3 py-2 text-sm"
            />
          </div>
          <div className="flex items-center gap-4">
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
        </div>
      </form>
    </div>
  );
}
