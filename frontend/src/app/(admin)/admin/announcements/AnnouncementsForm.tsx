"use client";

import Link from "next/link";
import { useState } from "react";
import type { AnnouncementAudience } from "@/lib/types";

const AUDIENCES: { value: AnnouncementAudience; label: string }[] = [
  { value: "unconfirmed", label: "Unconfirmed users" },
  { value: "confirmed", label: "Confirmed users" },
  { value: "all", label: "All users" },
  { value: "no_orders", label: "Users without orders" },
];

export default function AnnouncementsForm({
  audienceCounts,
}: {
  audienceCounts: Record<AnnouncementAudience, number>;
}) {
  const [from, setFrom] = useState("no-reply@rmafplas.com");
  const [subject, setSubject] = useState("");
  const [body, setBody] = useState("");
  const [sendTo, setSendTo] = useState<AnnouncementAudience | "">("");
  const [sending, setSending] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!sendTo) return;
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
    <form onSubmit={handleSubmit} autoComplete="off">
      <div className="form-group">
        <label className="label_">From:</label>
        <input
          className="form-control"
          type="email"
          placeholder="Announcement from email"
          required
          value={from}
          onChange={(e) => setFrom(e.target.value)}
        />
      </div>

      <div className="form-group">
        <label className="label_">Subject:</label>
        <input
          className="form-control"
          type="text"
          placeholder="Announcement email subject"
          required
          value={subject}
          onChange={(e) => setSubject(e.target.value)}
        />
      </div>

      <div className="form-group">
        <label className="label_">Message:</label>
        <textarea
          className="form-control"
          rows={10}
          placeholder="Announcement email body"
          required
          value={body}
          onChange={(e) => setBody(e.target.value)}
        />
      </div>

      {AUDIENCES.map((a) => (
        <label key={a.value} className="radio-inline">
          <input
            type="radio"
            name="send_to"
            value={a.value}
            required
            checked={sendTo === a.value}
            onChange={() => setSendTo(a.value)}
          />
          <label className="label_ text-uppercase">
            {a.label} ({audienceCounts[a.value] ?? 0})
            {a.value === "no_orders" && (
              <Link href="/admin/users-report">
                {" "}
                <i className="fa fa-question-circle"></i>
              </Link>
            )}
          </label>
        </label>
      ))}

      <div className="subBtn">
        <input
          className="btn btn-default text-uppercase"
          type="submit"
          value={sending ? "Sending..." : "Send announcement"}
          disabled={sending}
        />
        <Link href="/admin/orders" className="btn btn-default">
          CANCEL
        </Link>
      </div>
      <br />
      {message && (
        <p className={message.kind === "ok" ? "text-success" : "text-danger"}>{message.text}</p>
      )}
      <div className="alert alert-danger top-20">
        <strong>ATTENTION!</strong> Please be careful and double check before you click the &apos;Send
        announcement&apos; button. The emails will be sent out instantly.
      </div>
    </form>
  );
}
