"use client";

import { useState } from "react";

const PAGE_SIZE = 25;

/**
 * Client-side stand-in for the DataTables grids on the Blade report pages:
 * a search box over every column, and paging.
 */
export default function SearchableTable({
  columns,
  rows,
  numbered = false,
}: {
  columns: string[];
  rows: (string | number | null)[][];
  numbered?: boolean;
}) {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(0);

  const needle = search.trim().toLowerCase();
  const filtered = needle
    ? rows.filter((r) => r.some((cell) => String(cell ?? "").toLowerCase().includes(needle)))
    : rows;
  const pages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
  const current = Math.min(page, pages - 1);
  const visible = filtered.slice(current * PAGE_SIZE, (current + 1) * PAGE_SIZE);

  return (
    <>
      <div className="text-right">
        <label>
          Search:{" "}
          <input
            type="search"
            className="form-control input-sm"
            style={{ display: "inline-block", width: "auto" }}
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(0);
            }}
          />
        </label>
      </div>
      <table className="display table" cellSpacing={0}>
        <thead>
          <tr>
            {numbered && <th>#</th>}
            {columns.map((c) => (
              <th key={c}>{c}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {visible.map((row, i) => (
            <tr key={current * PAGE_SIZE + i}>
              {numbered && <td>{current * PAGE_SIZE + i + 1}</td>}
              {row.map((cell, j) => (
                <td key={j}>{cell}</td>
              ))}
            </tr>
          ))}
          {visible.length === 0 && (
            <tr>
              <td colSpan={columns.length + (numbered ? 1 : 0)}>No matching records found</td>
            </tr>
          )}
        </tbody>
      </table>
      <div className="clearfix">
        <span className="pull-left">
          Showing {filtered.length === 0 ? 0 : current * PAGE_SIZE + 1} to {current * PAGE_SIZE + visible.length} of{" "}
          {filtered.length} entries
        </span>
        <span className="pull-right">
          <button type="button" className="btn btn-sm btn-default" disabled={current === 0} onClick={() => setPage(current - 1)}>
            Previous
          </button>{" "}
          {current + 1} / {pages}{" "}
          <button
            type="button"
            className="btn btn-sm btn-default"
            disabled={current >= pages - 1}
            onClick={() => setPage(current + 1)}
          >
            Next
          </button>
        </span>
      </div>
    </>
  );
}
