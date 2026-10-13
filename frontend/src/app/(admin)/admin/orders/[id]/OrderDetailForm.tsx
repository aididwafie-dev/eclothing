"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import type { AdminOrderDetailResponse } from "@/lib/types";

const STATUS_LABELS: Record<string, string> = {
  "1": "Pending",
  "2": "Rejected",
  "3": "Approved",
  "5": "Processing",
  "6": "Completed",
};

export default function OrderDetailForm({ data }: { data: AdminOrderDetailResponse }) {
  const router = useRouter();
  const { order } = data;

  const [status, setStatus] = useState(
    Object.keys(STATUS_LABELS).find((code) => STATUS_LABELS[code]?.toLowerCase() === order.status) ??
      data.allowedStatuses[0] ??
      "1",
  );
  const [remarks, setRemarks] = useState(order.remarks ?? "");
  const [collectionDate, setCollectionDate] = useState(
    order.collectionDate ? order.collectionDate.slice(0, 10) : "",
  );
  const [collectionTime, setCollectionTime] = useState(
    order.collectionDate ? order.collectionDate.slice(11, 16) : "",
  );
  const [approvedQuantities, setApprovedQuantities] = useState<Record<string, string>>(
    Object.fromEntries(data.items.map((item) => [item.id, item.approvedQuantity?.toString() ?? ""])),
  );
  const [issueVouchers, setIssueVouchers] = useState<Record<string, string>>(
    Object.fromEntries(data.items.map((item) => [item.id, item.issueVoucher ?? ""])),
  );
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);

    try {
      const res = await fetch(`/api/admin/orders/${order.id}/status`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          status,
          remarks,
          collection_date: collectionDate || null,
          collection_time: collectionTime || null,
          approved_quantity: data.canApproveQuantities ? approvedQuantities : undefined,
          issue_voucher: data.canRecordIssueVouchers ? issueVouchers : undefined,
        }),
      });
      const result = await res.json();

      if (!res.ok) {
        setMessage({ kind: "error", text: result.message ?? "Could not update the order." });
        return;
      }

      setMessage({ kind: "ok", text: "Order status updated successfully." });
      router.refresh();
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="rounded-lg border border-black/10 bg-white p-6">
        <h1 className="text-xl font-semibold">Order #{order.id}</h1>
        <p className="text-sm text-black/50">{order.orderReference}</p>
        <dl className="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
          <div>
            <dt className="text-black/50">Service ID</dt>
            <dd>{order.sId ?? "-"}</dd>
          </div>
          <div>
            <dt className="text-black/50">Name</dt>
            <dd>{order.name ?? "N/A"}</dd>
          </div>
          <div>
            <dt className="text-black/50">Unit</dt>
            <dd>{order.unitName ?? "N/A"}</dd>
          </div>
          <div>
            <dt className="text-black/50">Uniform</dt>
            <dd>{order.uniformLabel}</dd>
          </div>
          <div>
            <dt className="text-black/50">Status</dt>
            <dd>{order.statusLabel}</dd>
          </div>
          <div>
            <dt className="text-black/50">Updated</dt>
            <dd>{order.updatedAt ?? "-"}</dd>
          </div>
        </dl>
      </div>

      <div className="rounded-lg border border-black/10 bg-white p-6">
        <h2 className="font-semibold">Items</h2>
        <table className="mt-3 w-full text-left text-sm">
          <thead>
            <tr className="border-b border-black/10 text-black/50">
              <th className="py-2">Uniform</th>
              <th className="py-2">Item</th>
              <th className="py-2">Size</th>
              <th className="py-2">Qty</th>
              {data.canApproveQuantities && <th className="py-2">Approved Qty</th>}
              {data.canRecordIssueVouchers && <th className="py-2">Issue Voucher</th>}
            </tr>
          </thead>
          <tbody>
            {data.items.map((item) => (
              <tr key={item.id} className="border-b border-black/5">
                <td className="py-2">{item.uniformLabel}</td>
                <td className="py-2">{item.clothes}</td>
                <td className="py-2">{item.size}</td>
                <td className="py-2">{item.quantity}</td>
                {data.canApproveQuantities && (
                  <td className="py-2">
                    <input
                      type="number"
                      min={0}
                      max={999}
                      value={approvedQuantities[item.id] ?? ""}
                      onChange={(e) =>
                        setApprovedQuantities((prev) => ({ ...prev, [item.id]: e.target.value }))
                      }
                      placeholder={String(item.quantity)}
                      className="w-20 rounded-md border border-black/15 px-2 py-1 text-sm"
                    />
                  </td>
                )}
                {data.canRecordIssueVouchers && (
                  <td className="py-2">
                    <input
                      type="text"
                      value={issueVouchers[item.id] ?? ""}
                      onChange={(e) =>
                        setIssueVouchers((prev) => ({ ...prev, [item.id]: e.target.value }))
                      }
                      className="w-32 rounded-md border border-black/15 px-2 py-1 text-sm"
                    />
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <form onSubmit={handleSubmit} className="rounded-lg border border-black/10 bg-white p-6">
        <h2 className="font-semibold">Update Status</h2>
        <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="block text-sm font-medium">Status</label>
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value)}
              className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
            >
              {data.allowedStatuses.map((code) => (
                <option key={code} value={code} disabled={code === "3" && data.isOwnOrder}>
                  {STATUS_LABELS[code] ?? code}
                  {code === "3" && data.isOwnOrder ? " (cannot approve own order)" : ""}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium">
              Remarks{status === "2" ? " (required)" : ""}
            </label>
            <input
              type="text"
              value={remarks}
              onChange={(e) => setRemarks(e.target.value)}
              required={status === "2"}
              maxLength={1000}
              className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-medium">Collection Date</label>
            <input
              type="date"
              value={collectionDate}
              onChange={(e) => setCollectionDate(e.target.value)}
              className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-medium">Collection Time</label>
            <input
              type="time"
              value={collectionTime}
              onChange={(e) => setCollectionTime(e.target.value)}
              className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
            />
          </div>
        </div>
        <div className="mt-4 flex items-center gap-4">
          <button
            type="submit"
            disabled={saving}
            className="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50"
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
    </div>
  );
}
