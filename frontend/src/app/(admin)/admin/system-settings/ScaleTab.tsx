"use client";

import { useState } from "react";
import { apiPost } from "@/lib/clientApi";
import type { ScaleResponse } from "@/lib/types";

export default function ScaleTab({ initialData }: { initialData: ScaleResponse }) {
  const [data, setData] = useState(initialData);
  const [rankId, setRankId] = useState(initialData.selectedRankId ?? "");
  const [loading, setLoading] = useState(false);

  async function changeRank(id: string) {
    setRankId(id);
    setLoading(true);
    try {
      const res = await fetch(`/api/admin/system-settings/scale?pangkatId=${id}`);
      setData(await res.json());
    } finally {
      setLoading(false);
    }
  }

  async function toggleHidden(uniformId: string, hidden: boolean) {
    setData((prev) => ({
      ...prev,
      uniforms: prev.uniforms.map((u) => (u.uniform.id === uniformId ? { ...u, hidden } : u)),
    }));
    await apiPost("/api/admin/system-settings/scale/uniform-visibility", { pangkatId: rankId, uniformsId: uniformId, hidden });
  }

  async function saveItem(clothesId: string, value: string) {
    const res = await apiPost("/api/admin/system-settings/scale/item", { pangkatId: rankId, uniformClothesId: clothesId, value });
    const result = await res.json();
    if (result.ok) {
      setData((prev) => ({
        ...prev,
        uniforms: prev.uniforms.map((u) => ({
          ...u,
          items: u.items.map((i) => (i.id === clothesId ? { ...i, maxQuantity: result.value } : i)),
        })),
      }));
    }
  }

  if (!data.storageReady) {
    return <p className="text-sm text-red-600">Scale table does not exist yet. Run &quot;php artisan migrate&quot;.</p>;
  }

  return (
    <div className="flex flex-col gap-6">
      <div>
        <label className="block text-sm font-medium">Rank (Air Force only)</label>
        <select
          value={rankId}
          onChange={(e) => changeRank(e.target.value)}
          className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
        >
          <optgroup label="Officer">
            {data.ranks.officer.map((r) => (
              <option key={r.id} value={r.id}>
                {r.value}
              </option>
            ))}
          </optgroup>
          <optgroup label="Non-Officer">
            {data.ranks.nonOfficer.map((r) => (
              <option key={r.id} value={r.id}>
                {r.value}
              </option>
            ))}
          </optgroup>
        </select>
      </div>

      {loading ? (
        <p className="text-sm text-black/50">Loading...</p>
      ) : (
        <div className="flex flex-col gap-4">
          {data.uniforms.map((group) => (
            <div key={group.uniform.id} className="rounded-md border border-black/10 p-4">
              <div className="flex items-center justify-between">
                <h3 className="font-semibold">{group.uniform.label}</h3>
                {data.hiddenStorageReady && (
                  <label className="flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      checked={group.hidden}
                      onChange={(e) => toggleHidden(group.uniform.id, e.target.checked)}
                    />
                    Hide entire uniform for this rank
                  </label>
                )}
              </div>
              <table className="mt-3 w-full text-left text-sm">
                <thead>
                  <tr className="text-black/50">
                    <th className="py-1">Item</th>
                    <th className="py-1">Max Quantity (blank = no cap, 0 = blocked)</th>
                  </tr>
                </thead>
                <tbody>
                  {group.items.map((item) => (
                    <tr key={item.id} className="border-t border-black/5">
                      <td className="py-1">{item.clothesType}</td>
                      <td className="py-1">
                        <ScaleInput disabled={group.hidden} item={item} onSave={saveItem} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

function ScaleInput({
  item,
  disabled,
  onSave,
}: {
  item: { id: string; maxQuantity: number | null };
  disabled: boolean;
  onSave: (id: string, value: string) => void;
}) {
  const [value, setValue] = useState(item.maxQuantity === null ? "" : String(item.maxQuantity));

  return (
    <input
      type="number"
      min={0}
      max={999}
      disabled={disabled}
      value={value}
      onChange={(e) => setValue(e.target.value)}
      onBlur={() => onSave(item.id, value)}
      className="w-24 rounded-md border border-black/15 px-2 py-1 text-sm disabled:opacity-50"
    />
  );
}
