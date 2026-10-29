"use client";

import Link from "next/link";
import { useState } from "react";
import { apiDelete } from "@/lib/clientApi";
import type { AdminTred } from "@/lib/types";

const OFFICER_RECRUIT: Record<number, string> = {
  1: "Officer",
  2: "Other Rank",
  3: "Both officer & other rank",
};

export default function TredPageClient({ initialTreds }: { initialTreds: AdminTred[] }) {
  const [treds, setTreds] = useState(initialTreds);

  async function handleDelete(tred: AdminTred) {
    if (!confirm("Are you sure you want to remove this tred from the site permanently?")) return;
    const res = await apiDelete(`/api/admin/treds/${tred.id}`);
    if (res.ok) {
      setTreds((prev) => prev.filter((t) => t.id !== tred.id));
    }
  }

  return (
    <>
      <div className="text-center">
        <Link href="/admin/tred/add" className="btn btn-primary">
          <i className="fa fa-plus" aria-hidden="true"></i> ADD NEW TRED
        </Link>
      </div>
      <div className="content full table-responsive">
        <table className="table table-orders" style={{ width: "100%" }}>
          <thead>
            <tr>
              <th>#</th>
              <th>Tred name</th>
              <th>Officer recruit</th>
              <th>Edit</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody>
            {treds.map((tred, i) => (
              <tr key={tred.id}>
                <td data-label="#">{i + 1}</td>
                <td data-label="Tred name">{tred.value}</td>
                <td data-label="Officer recruit">{OFFICER_RECRUIT[tred.officerRecruit] ?? ""}</td>
                <td data-label="Edit">
                  <Link href={`/admin/tred/${tred.id}`} className="btn btn-sm btn-primary">
                    <span className="glyphicon glyphicon-edit"></span>
                  </Link>
                </td>
                <td data-label="Delete">
                  <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDelete(tred)}>
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
