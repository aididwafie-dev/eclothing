"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import type { PersonalInventoryResponse } from "@/lib/types";

export default function PersonalInventoryFilters({
  data,
  currentSearch,
}: {
  data: PersonalInventoryResponse;
  currentSearch: string;
}) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [search, setSearch] = useState(currentSearch);

  function updateParam(name: string, value: string) {
    const next = new URLSearchParams(searchParams.toString());
    if (value && value !== "all") {
      next.set(name, value);
    } else {
      next.delete(name);
    }
    next.delete("page");
    router.push(`/admin/personal-inventory?${next}`);
  }

  function handleSearchSubmit(e: React.FormEvent) {
    e.preventDefault();
    updateParam("search", search);
  }

  const totalPages = Math.max(1, Math.ceil(data.recordsFiltered / data.perPage));
  const uniformColumns =
    data.filters.uniform === "all"
      ? data.filters.uniformOptions
      : data.filters.uniformOptions.filter((u) => u.id === data.filters.uniform);

  return (
    <div className="mt-4">
      <div className="flex flex-wrap items-end gap-4">
        <div>
          <label className="block text-sm font-medium">Uniform</label>
          <select
            value={data.filters.uniform}
            onChange={(e) => updateParam("uniform", e.target.value)}
            className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
          >
            <option value="all">All Uniforms</option>
            {data.filters.uniformOptions.map((u) => (
              <option key={u.id} value={u.id}>
                {u.label}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium">Month</label>
          <select
            value={data.filters.month}
            onChange={(e) => updateParam("month", e.target.value)}
            className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
          >
            <option value="all">All Months</option>
            {data.filters.monthOptions.map((m) => (
              <option key={m.value} value={m.value}>
                {m.label}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium">Year</label>
          <select
            value={data.filters.year}
            onChange={(e) => updateParam("year", e.target.value)}
            className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
          >
            <option value="all">All Years</option>
            {data.filters.yearOptions.map((y) => (
              <option key={y} value={y}>
                {y}
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
              placeholder="Service ID, name, unit..."
              className="mt-1 w-56 rounded-md border border-black/15 px-3 py-2 text-sm"
            />
          </div>
          <button type="submit" className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white">
            Search
          </button>
        </form>
      </div>

      <div className="mt-6 overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-black/10 text-black/50">
              <th className="py-2">Service ID</th>
              <th className="py-2">Name</th>
              <th className="py-2">Unit</th>
              {uniformColumns.map((u) => (
                <th key={u.id} className="py-2">
                  {u.label}
                </th>
              ))}
              <th className="py-2">Orders</th>
              <th className="py-2">Total</th>
            </tr>
          </thead>
          <tbody>
            {data.rows.length === 0 && (
              <tr>
                <td colSpan={5 + uniformColumns.length} className="py-6 text-center text-black/50">
                  No members match this filter.
                </td>
              </tr>
            )}
            {data.rows.map((row, i) => (
              <tr key={row.sId ?? i} className="border-b border-black/5">
                <td className="py-2">
                  {row.sId ? (
                    <Link href={`/admin/personal-inventory/${row.sId}`} className="text-blue-600 hover:underline">
                      {row.sId}
                    </Link>
                  ) : (
                    "-"
                  )}
                </td>
                <td className="py-2">{row.name ?? "N/A"}</td>
                <td className="py-2">{row.unitName ?? "N/A"}</td>
                {uniformColumns.map((col) => {
                  const match = row.uniformCounts.find((c) => c.uniformId === col.id);
                  return (
                    <td key={col.id} className="py-2" title={`${match?.items ?? 0} item(s) across ${match?.orders ?? 0} order(s)`}>
                      {match?.items ?? 0}
                    </td>
                  );
                })}
                <td className="py-2">{row.orderCount}</td>
                <td className="py-2">{row.itemCount}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

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
