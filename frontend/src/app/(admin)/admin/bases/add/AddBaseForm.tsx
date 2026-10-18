"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { apiUrl } from "@/lib/clientApi";

export default function AddBaseForm() {
  const router = useRouter();
  const [baseName, setBaseName] = useState("");
  const [orgName, setOrgName] = useState("");
  const [active, setActive] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);
    try {
      const res = await fetch(apiUrl("/api/admin/bases"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ base: baseName, orgName, active }),
      });
      const data = await res.json();
      if (!res.ok) {
        setMessage({ kind: "error", text: data.message ?? "Could not create the base." });
        return;
      }
      router.push("/admin/bases");
      router.refresh();
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} autoComplete="off">
      <label className="label_">Base name</label>
      <input
        type="text"
        className="form-control"
        maxLength={255}
        required
        value={baseName}
        onChange={(e) => setBaseName(e.target.value)}
      />
      <br />
      <label className="label_">Org name</label>
      <input
        type="text"
        className="form-control"
        maxLength={255}
        value={orgName}
        onChange={(e) => setOrgName(e.target.value)}
      />
      <br />
      <label className="label_">Status</label>
      <select className="form-control" value={active ? "1" : "0"} onChange={(e) => setActive(e.target.value === "1")}>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>

      {message && <p className={message.kind === "ok" ? "text-success" : "text-danger"}>{message.text}</p>}

      <div className="subBtn text-center">
        <br />
        <button className="btn btn-primary" type="submit" disabled={saving}>
          {saving ? "Saving..." : "SAVE BASE"}
        </button>
        <Link href="/admin/bases" className="btn btn-default">
          {" "}
          CANCEL
        </Link>
      </div>
    </form>
  );
}
