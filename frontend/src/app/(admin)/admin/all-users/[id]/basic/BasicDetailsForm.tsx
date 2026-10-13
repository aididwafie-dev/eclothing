"use client";

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
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="mx-auto max-w-md rounded-lg border border-black/10 bg-white p-6">
      <h1 className="text-xl font-semibold">Edit Basic Details</h1>
      <div className="mt-4 flex flex-col gap-4">
        <div>
          <label className="block text-sm font-medium">Service ID</label>
          <input
            type="text"
            required
            maxLength={7}
            value={sId}
            onChange={(e) => setSId(e.target.value)}
            className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium">New Password (leave blank to keep current)</label>
          <input
            type="password"
            minLength={8}
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
      </div>
      <div className="mt-6 flex items-center gap-4">
        <button
          type="submit"
          disabled={saving}
          className="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
        >
          {saving ? "Saving..." : "Save"}
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
