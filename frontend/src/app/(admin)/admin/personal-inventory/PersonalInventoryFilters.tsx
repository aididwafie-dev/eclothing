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
    updateParam("search", search.trim());
  }

  const totalPages = Math.max(1, Math.ceil(data.recordsFiltered / data.perPage));
  const uniformColumns =
    data.filters.uniform === "all"
      ? data.filters.uniformOptions
      : data.filters.uniformOptions.filter((u) => u.id === data.filters.uniform);

  const hasActiveFilter =
    currentSearch.trim() !== "" || data.filters.uniform !== "all" || data.filters.month !== "all" || data.filters.year !== "all";

  const uniformLabel =
    data.filters.uniform === "all"
      ? "all uniform types"
      : `uniform ${data.filters.uniformOptions.find((u) => u.id === data.filters.uniform)?.label ?? ""}`;
  const periodLabel =
    data.filters.month === "all" && data.filters.year === "all"
      ? "all dates"
      : [
          data.filters.month === "all"
            ? ""
            : data.filters.monthOptions.find((m) => m.value === data.filters.month)?.label ?? "",
          data.filters.year === "all" ? "every year" : data.filters.year,
        ]
          .filter(Boolean)
          .join(" ");

  return (
    <>
      <form className="orders-search" role="search" onSubmit={handleSearchSubmit}>
        <div className="orders-search-field">
          <i className="fa fa-search" aria-hidden="true"></i>
          <input
            type="text"
            className="form-control"
            placeholder="Service ID, name or unit"
            aria-label="Search members"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            autoComplete="off"
          />
        </div>
        <div className="orders-filter-field">
          <label className="orders-filter-label">Uniform</label>
          <select
            className="form-control"
            value={data.filters.uniform}
            onChange={(e) => updateParam("uniform", e.target.value)}
          >
            <option value="all">All uniform types</option>
            {data.filters.uniformOptions.map((u) => (
              <option key={u.id} value={u.id}>
                {u.label}
              </option>
            ))}
          </select>
        </div>
        <div className="orders-filter-field">
          <label className="orders-filter-label">Month</label>
          <select
            className="form-control"
            value={data.filters.month}
            onChange={(e) => updateParam("month", e.target.value)}
          >
            <option value="all">All months</option>
            {data.filters.monthOptions.map((m) => (
              <option key={m.value} value={m.value}>
                {m.label}
              </option>
            ))}
          </select>
        </div>
        <div className="orders-filter-field">
          <label className="orders-filter-label">Year</label>
          <select
            className="form-control"
            value={data.filters.year}
            onChange={(e) => updateParam("year", e.target.value)}
          >
            <option value="all">All years</option>
            {data.filters.yearOptions.map((y) => (
              <option key={y} value={y}>
                {y}
              </option>
            ))}
          </select>
        </div>
        {hasActiveFilter && (
          <Link href="/admin/personal-inventory" className="btn btn-default">
            <i className="fa fa-times" aria-hidden="true"></i> Clear
          </Link>
        )}
      </form>

      <div className="orders-search-result">
        Showing <strong>{data.recordsFiltered}</strong> member(s), {uniformLabel}, {periodLabel}
        {currentSearch.trim() !== "" ? (
          <>
            , matching <strong>{currentSearch}</strong>
          </>
        ) : null}
        .
      </div>

      <div className="table-responsive">
        <table className="table table-orders table-inventory" style={{ width: "100%" }}>
          <thead>
            <tr>
              <th>Service ID</th>
              <th>Name</th>
              <th>Unit</th>
              {uniformColumns.map((u) => (
                <th key={u.id} className="inventory-head" title={u.label}>
                  {u.label}
                </th>
              ))}
              <th className="inventory-head">Orders</th>
              <th className="inventory-head">Total</th>
            </tr>
          </thead>
          <tbody>
            {data.rows.length === 0 && (
              <tr>
                <td colSpan={5 + uniformColumns.length} className="text-center">
                  No member matches.
                </td>
              </tr>
            )}
            {data.rows.map((row, i) => (
              <tr
                key={row.sId ?? i}
                className="inventory-row"
                style={{ cursor: row.sId ? "pointer" : undefined }}
                onClick={() => {
                  if (row.sId) window.open(`/admin/personal-inventory/${row.sId}`, "_blank", "noopener");
                }}
              >
                <td data-label="Service ID">
                  {row.sId ? (
                    <Link href={`/admin/personal-inventory/${row.sId}`} onClick={(e) => e.stopPropagation()}>
                      {row.sId}
                    </Link>
                  ) : (
                    "-"
                  )}
                </td>
                <td data-label="Name">{row.name ?? "N/A"}</td>
                <td data-label="Unit">{row.unitName ?? "N/A"}</td>
                {uniformColumns.map((col) => {
                  const match = row.uniformCounts.find((c) => c.uniformId === col.id);
                  const items = match?.items ?? 0;
                  return (
                    <td
                      key={col.id}
                      data-label={col.label}
                      className={`inventory-cell${items === 0 ? " is-zero" : ""}`}
                      title={`${items} item(s) across ${match?.orders ?? 0} order(s)`}
                    >
                      {items}
                    </td>
                  );
                })}
                <td data-label="Orders" className="inventory-cell">
                  {row.orderCount}
                </td>
                <td data-label="Total" className="inventory-cell inventory-total">
                  {row.itemCount}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {totalPages > 1 && (
        <ul className="pagination">
          {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
            <li key={p} className={p === data.page ? "active" : ""}>
              <a onClick={() => updateParam("page", String(p))} style={{ cursor: "pointer" }}>
                {p}
              </a>
            </li>
          ))}
        </ul>
      )}
    </>
  );
}
