"use client";

import Link from "next/link";
import { useState } from "react";
import { apiDelete } from "@/lib/clientApi";
import type { AdminBase } from "@/lib/types";

export default function BasesPageClient({ initialBases }: { initialBases: AdminBase[] }) {
  const [bases, setBases] = useState(initialBases);

  async function handleDelete(base: AdminBase) {
    if (
      !confirm(
        "Are you sure you want to remove this base from the site permanently? Its units will keep existing but lose this base.",
      )
    ) {
      return;
    }
    const res = await apiDelete(`/api/admin/bases/${base.id}`);
    if (res.ok) {
      setBases((prev) => prev.filter((b) => b.id !== base.id));
    }
  }

  return (
    <>
      <div className="text-center">
        <Link href="/admin/bases/add" className="btn btn-primary">
          <i className="fa fa-plus" aria-hidden="true"></i> ADD NEW BASE
        </Link>
      </div>
      <div className="content full table-responsive">
        <table className="table table-orders" style={{ width: "100%" }}>
          <thead>
            <tr>
              <th>#</th>
              <th>Base</th>
              <th>Org name</th>
              <th>Status</th>
              <th>Units</th>
              <th>Edit</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody>
            {bases.map((base, i) => (
              <tr key={base.id}>
                <td data-label="#">{i + 1}</td>
                <td data-label="Base">{base.base}</td>
                <td data-label="Org name">{base.orgName ?? ""}</td>
                <td data-label="Status">{base.active ? "Active" : "Inactive"}</td>
                <td data-label="Units">{base.unitCount}</td>
                <td data-label="Edit">
                  <Link href={`/admin/bases/${base.id}`} className="btn btn-sm btn-primary">
                    <span className="glyphicon glyphicon-edit"></span>
                  </Link>
                </td>
                <td data-label="Delete">
                  <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDelete(base)}>
                    <span className="glyphicon glyphicon-trash"></span>
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
