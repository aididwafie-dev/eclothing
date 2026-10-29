"use client";

import Link from "next/link";
import { useState } from "react";
import { apiDelete } from "@/lib/clientApi";
import type { AdminUniformItemRow, UniformItemKind } from "@/lib/types";

/** clothesList.blade.php and accessoriesList.blade.php -- the same table with different labels. */
export default function UniformItemsList({
  uniformId,
  kind,
  initialItems,
}: {
  uniformId: string;
  kind: UniformItemKind;
  initialItems: AdminUniformItemRow[];
}) {
  const [items, setItems] = useState(initialItems);
  const isAccessory = kind === "accessories";

  async function handleDelete(item: AdminUniformItemRow) {
    const prompt = isAccessory
      ? "Users may have ordered this accessory. Are you sure you want to remove this accessory?"
      : "Users may have orderd this Cloth. Are you sure you want to remove this cloth?";
    if (!confirm(prompt)) return;
    const res = await apiDelete(`/api/admin/uniform-clothes/${item.id}`);
    if (res.ok) {
      setItems((prev) => prev.filter((i) => i.id !== item.id));
    }
  }

  const all = (v: string | null) => v || "-ALL-";

  return (
    <>
      <div className="text-center">
        <Link
          href={`/admin/uniforms/${uniformId}/${kind}/add`}
          className={isAccessory ? "btn btn-primary" : "btn btn-xs btn-primary"}
        >
          {isAccessory ? (
            <>
              <i className="fa fa-plus" aria-hidden="true"></i> ADD NEW ACCESSORY
            </>
          ) : (
            <>
              <span className="glyphicon glyphicon-plus-sign"></span> ADD NEW CLOTH
            </>
          )}
        </Link>
      </div>
      <div className="content full table-responsive">
        <table className="table table-orders" style={{ width: "100%" }}>
          <thead>
            <tr>
              <th>{isAccessory ? "Accessory name" : "Clothes type"}</th>
              <th>{isAccessory ? "Options" : "Clothes size"}</th>
              <th>Gender</th>
              <th>Rank</th>
              <th>Tred</th>
              <th>Religion</th>
              <th>Edit</th>
              <th>Remove</th>
            </tr>
          </thead>
          <tbody>
            {items.map((item) => (
              <tr key={item.id}>
                <td data-label={isAccessory ? "Accessory name" : "Clothes type"}>{item.clothesType}</td>
                <td data-label={isAccessory ? "Options" : "Clothes size"}>{item.clothesSize}</td>
                <td data-label="Gender">{all(item.gender)}</td>
                <td data-label="Rank">{all(item.rank)}</td>
                <td data-label="Tred">{all(item.tred)}</td>
                <td data-label="Religion">{all(item.religion)}</td>
                <td data-label="Edit">
                  <Link href={`/admin/uniforms/items/${item.id}`} className="btn btn-sm btn-primary">
                    <span className="glyphicon glyphicon-edit"></span>
                  </Link>
                </td>
                <td data-label="Remove">
                  <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDelete(item)}>
                    <span className="glyphicon glyphicon-trash"></span>
                  </button>
                </td>
              </tr>
            ))}
            {items.length === 0 && (
              <tr>
                <td colSpan={8}>No data available in table</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </>
  );
}
