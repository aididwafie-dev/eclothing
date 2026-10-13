"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import type { AdminOrdersResponse } from "@/lib/types";

export default function OrdersFilters({
  data,
  currentSearch,
}: {
  data: AdminOrdersResponse;
  currentSearch: string;
}) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [search, setSearch] = useState(currentSearch);

  function updateParam(name: string, value: string) {
    const next = new URLSearchParams(searchParams.toString());
    if (value) {
      next.set(name, value);
    } else {
      next.delete(name);
    }
    next.delete("page");
    router.push(`/admin/orders?${next}`);
  }

  function handleSearchSubmit(e: React.FormEvent) {
    e.preventDefault();
    updateParam("search", search);
  }

  const totalPages = Math.max(1, Math.ceil(data.recordsFiltered / data.perPage));

  return (
    <div className="mt-4">
      <div className="flex flex-wrap items-end gap-4">
        <div>
          <label className="block text-sm font-medium">Status</label>
          <select
            value={data.filters.status}
            onChange={(e) => updateParam("status", e.target.value)}
            className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
          >
            <option value="all">All</option>
            {data.filters.statusOptions.map((opt) => (
              <option key={opt.key} value={opt.key}>
                {opt.label}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium">Unit</label>
          <select
            value={data.filters.unit ?? ""}
            onChange={(e) => updateParam("unit", e.target.value)}
            className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
          >
            <option value="">All Units</option>
            {data.filters.unitOptions.map((opt) => (
              <option key={opt.id} value={opt.id}>
                {opt.value}
              </option>
            ))}
          </select>
        </div>
        <form onSubmit={handleSearchSubmit} className="flex items-end gap-2">
          <div>
            <label className="block text-sm font-medium">Search</label>
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Order #, name, s_id, unit, uniform..."
              className="mt-1 w-64 rounded-md border border-black/15 px-3 py-2 text-sm"
            />
          </div>
          <button type="submit" className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white">
            Search
          </button>
        </form>
      </div>

      <table className="mt-6 w-full text-left text-sm">
        <thead>
          <tr className="border-b border-black/10 text-black/50">
            <th className="py-2">Order</th>
            <th className="py-2">Service ID</th>
            <th className="py-2">Name</th>
            <th className="py-2">Unit</th>
            <th className="py-2">Uniform</th>
            <th className="py-2">Items</th>
            <th className="py-2">Status</th>
            <th className="py-2">Updated</th>
            <th className="py-2"></th>
          </tr>
        </thead>
        <tbody>
          {data.orders.length === 0 && (
            <tr>
              <td colSpan={9} className="py-6 text-center text-black/50">
                No orders match this filter.
              </td>
            </tr>
          )}
          {data.orders.map((order) => (
            <tr key={order.id} className="border-b border-black/5">
              <td className="py-2">#{order.id}</td>
              <td className="py-2">{order.sId ?? "-"}</td>
              <td className="py-2">{order.name ?? "N/A"}</td>
              <td className="py-2">{order.unitName ?? "N/A"}</td>
              <td className="py-2">{order.uniformLabel}</td>
              <td className="py-2">{order.itemCount}</td>
              <td className="py-2">
                <span className="rounded bg-black/5 px-2 py-0.5">{order.statusLabel}</span>
              </td>
              <td className="py-2">{order.updatedAt ?? "-"}</td>
              <td className="py-2">
                <Link href={`/admin/orders/${order.id}`} className="text-blue-600 hover:underline">
                  View
                </Link>
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      {totalPages > 1 && (
        <div className="mt-4 flex items-center gap-2 text-sm">
          {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
            <button
              key={p}
              onClick={() => updateParam("page", String(p))}
              className={`rounded px-2 py-1 ${p === data.page ? "bg-slate-900 text-white" : "hover:bg-black/5"}`}
            >
              {p}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
