"use client";

import Link from "next/link";
import { useState } from "react";
import type { AdminUserDetailResponse } from "@/lib/types";

export default function BasicDetailsForm({
  userId,
  user,
}: {
  userId: string;
  user: AdminUserDetailResponse["user"];
}) {
  const [sId, setSId] = useState(user.sId);
  const [password, setPassword] = useState("");
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);
    try {
      const res = await fetch(`/api/admin/users/${userId}/basic`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ sId, password }),
      });
      const data = await res.json();
      if (!res.ok) {
        setMessage({ kind: "error", text: data.message ?? "Could not save." });
        return;
      }
      setMessage({ kind: "ok", text: data.message });
      setPassword("");
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} autoComplete="off">
      <div className="form-group">
        <label className="label_">Service ID:</label>
        <input
          className="form-control"
          type="text"
          maxLength={7}
          value={sId}
          onChange={(e) => setSId(e.target.value)}
          placeholder="Write new service id...."
        />
      </div>

      <div className="form-group">
        <label className="label_">Password:</label>
        <input
          className="form-control"
          type="password"
          minLength={8}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          placeholder="Write new password...."
        />
      </div>

      {message && (
        <p className={message.kind === "ok" ? "text-success" : "text-danger"}>{message.text}</p>
      )}

      <div className="subBtn">
        <input className="btn btn-default" type="submit" value={saving ? "Saving..." : "Save"} disabled={saving} />
        <Link href="/admin/all-users" className="btn btn-default">
          CANCEL
        </Link>
      </div>
    </form>
  );
}
