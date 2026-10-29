"use client";

import Link from "next/link";
import { useState } from "react";
import { apiDelete } from "@/lib/clientApi";
import type { AdminUnit } from "@/lib/types";

export default function UnitsPageClient({ initialUnits }: { initialUnits: AdminUnit[] }) {
  const [units, setUnits] = useState(initialUnits);

  async function handleDelete(unit: AdminUnit) {
    if (!confirm("Are you sure you want to remove this unit from the site permanently?")) return;
    const res = await apiDelete(`/api/admin/units/${unit.id}`);
    if (res.ok) {
      setUnits((prev) => prev.filter((u) => u.id !== unit.id));
    }
  }

  return (
    <>
      <div className="text-center">
        <Link href="/admin/units/add" className="btn btn-primary">
          <i className="fa fa-plus" aria-hidden="true"></i> ADD NEW UNIT
        </Link>
      </div>
      <div className="content full table-responsive">
        <table className="table table-orders" style={{ width: "100%" }}>
          <thead>
            <tr>
              <th>#</th>
              <th>Name of unit</th>
              <th>BASE/HQ</th>
              <th>ORG</th>
              <th>Edit</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody>
            {units.map((unit, i) => (
              <tr key={unit.id}>
                <td data-label="#">{i + 1}</td>
                <td data-label="Name of unit">{unit.value}</td>
                <td data-label="BASE/HQ">{unit.baseHqName ?? ""}</td>
                <td data-label="ORG">{unit.org ?? ""}</td>
                <td data-label="Edit">
                  <Link href={`/admin/units/${unit.id}`} className="btn btn-sm btn-primary">
                    <span className="glyphicon glyphicon-edit"></span>
                  </Link>
                </td>
                <td data-label="Delete">
                  <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDelete(unit)}>
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
