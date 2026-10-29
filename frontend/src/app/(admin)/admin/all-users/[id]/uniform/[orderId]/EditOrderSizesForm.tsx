"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { apiPut } from "@/lib/clientApi";
import type { AdminUserOrderDetail } from "@/lib/types";

export default function EditOrderSizesForm({ userId, order }: { userId: string; order: AdminUserOrderDetail }) {
  const router = useRouter();
  const [sizes, setSizes] = useState<Record<string, string>>(() =>
    Object.fromEntries(order.lines.map((l) => [l.id, l.size])),
  );
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const backHref = `/admin/all-users/${userId}/uniform`;

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      const res = await apiPut(`/api/admin/user-orders/${order.id}`, { sizes });
      const data = await res.json();
      if (!res.ok) {
        setError(data.message ?? "Could not save the sizes.");
        return;
      }
      router.push(backHref);
      router.refresh();
    } catch {
      setError("Could not reach the server.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <form autoComplete="off" onSubmit={handleSubmit}>
      {order.lines.map((line) => (
        <div key={line.id}>
          <label className="label_">{line.clothes.split("_").join(" ")}</label>
          {line.size === "FIX" ? (
            <input className="form-control" type="text" value={line.size} readOnly />
          ) : (
            <input
              className="form-control size_order"
              type="text"
              required
              value={sizes[line.id] ?? ""}
              onChange={(e) => setSizes((s) => ({ ...s, [line.id]: e.target.value.toUpperCase() }))}
            />
          )}
        </div>
      ))}

      {error && <p className="text-danger">{error}</p>}

      <div className="subBtn">
        <input className="btn btn-default" type="submit" value={saving ? "Saving..." : "SAVE"} disabled={saving} />{" "}
        <Link className="btn btn-default" href={backHref}>
          CANCEL
        </Link>
      </div>
    </form>
  );
}
