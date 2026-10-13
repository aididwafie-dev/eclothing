"use client";

import { useState } from "react";
import { apiPut } from "@/lib/clientApi";
import type { UniformGroup } from "@/lib/types";

export default function UniformTab({ initialGroups }: { initialGroups: UniformGroup[] }) {
  const [groups, setGroups] = useState(initialGroups);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  const [newType, setNewType] = useState("");
  const [newName, setNewName] = useState("");
  const [newPhoto, setNewPhoto] = useState<File | null>(null);
  const [addingUniform, setAddingUniform] = useState(false);

  const [itemUniformId, setItemUniformId] = useState(initialGroups[0]?.uniform.id ?? "");
  const [itemType, setItemType] = useState("");
  const [itemSize, setItemSize] = useState("");
  const [itemKind, setItemKind] = useState<"clothes" | "accessory">("clothes");
  const [itemPhoto, setItemPhoto] = useState<File | null>(null);
  const [addingItem, setAddingItem] = useState(false);

  async function refresh() {
    const data = await fetch("/api/admin/system-settings/uniforms").then((r) => r.json());
    setGroups(data.groups ?? []);
  }

  async function handleAddUniform(e: React.FormEvent) {
    e.preventDefault();
    setAddingUniform(true);
    setMessage(null);
    try {
      const formData = new FormData();
      formData.append("uniformType", newType);
      formData.append("uniformName", newName);
      if (newPhoto) formData.append("photo", newPhoto);
      const res = await fetch("/api/admin/system-settings/uniforms", { method: "POST", body: formData });
      const data = await res.json();
      setMessage({ kind: res.ok ? "ok" : "error", text: data.message });
      if (res.ok) {
        setNewType("");
        setNewName("");
        setNewPhoto(null);
        await refresh();
      }
    } finally {
      setAddingUniform(false);
    }
  }

  async function handleAddItem(e: React.FormEvent) {
    e.preventDefault();
    setAddingItem(true);
    setMessage(null);
    try {
      const formData = new FormData();
      formData.append("uniformsId", itemUniformId);
      formData.append("clothesType", itemType);
      formData.append("clothesSize", itemSize);
      formData.append("itemKind", itemKind);
      if (itemPhoto) formData.append("photo", itemPhoto);
      const res = await fetch("/api/admin/system-settings/uniform-items", { method: "POST", body: formData });
      const data = await res.json();
      setMessage({ kind: res.ok ? "ok" : "error", text: data.message });
      if (res.ok) {
        setItemType("");
        setItemSize("");
        await refresh();
      }
    } finally {
      setAddingItem(false);
    }
  }

  async function renameUniform(id: string, uniformType: string, uniformName: string) {
    const res = await apiPut(`/api/admin/system-settings/uniforms/${id}`, { uniformType, uniformName });
    const data = await res.json();
    setMessage({ kind: res.ok ? "ok" : "error", text: data.message });
    if (res.ok) await refresh();
  }

  async function renameItem(id: string, clothesType: string, clothesSize: string) {
    const res = await apiPut(`/api/admin/system-settings/uniform-items/${id}`, { clothesType, clothesSize });
    const data = await res.json();
    setMessage({ kind: res.ok ? "ok" : "error", text: data.message });
    if (res.ok) await refresh();
  }

  return (
    <div className="flex flex-col gap-8">
      {message && (
        <p className={message.kind === "ok" ? "text-sm text-green-700" : "text-sm text-red-600"}>{message.text}</p>
      )}

      <form onSubmit={handleAddUniform} className="flex flex-wrap items-end gap-3 rounded-md border border-black/10 p-4">
        <h2 className="w-full font-semibold">Add Uniform</h2>
        <div>
          <label className="block text-sm font-medium">Code (max 3 chars)</label>
          <input
            type="text"
            required
            maxLength={3}
            value={newType}
            onChange={(e) => setNewType(e.target.value)}
            className="mt-1 w-24 rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium">Name</label>
          <input
            type="text"
            value={newName}
            onChange={(e) => setNewName(e.target.value)}
            className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium">Photo</label>
          <input type="file" accept=".png,.jpg,.jpeg" onChange={(e) => setNewPhoto(e.target.files?.[0] ?? null)} className="mt-1 text-sm" />
        </div>
        <button type="submit" disabled={addingUniform} className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50">
          Add
        </button>
      </form>

      <form onSubmit={handleAddItem} className="flex flex-wrap items-end gap-3 rounded-md border border-black/10 p-4">
        <h2 className="w-full font-semibold">Add Item / Accessory</h2>
        <div>
          <label className="block text-sm font-medium">Uniform</label>
          <select
            value={itemUniformId}
            onChange={(e) => setItemUniformId(e.target.value)}
            className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
          >
            {groups.map((g) => (
              <option key={g.uniform.id} value={g.uniform.id}>
                {g.uniform.uniformName ?? g.uniform.uniformType}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium">Name</label>
          <input
            type="text"
            required
            value={itemType}
            onChange={(e) => setItemType(e.target.value)}
            className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium">Size rule</label>
          <input
            type="text"
            value={itemSize}
            onChange={(e) => setItemSize(e.target.value)}
            placeholder="blank / FIX / S | M | L / 28-44"
            className="mt-1 w-48 rounded-md border border-black/15 px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium">Kind</label>
          <select
            value={itemKind}
            onChange={(e) => setItemKind(e.target.value as "clothes" | "accessory")}
            className="mt-1 rounded-md border border-black/15 px-3 py-2 text-sm"
          >
            <option value="clothes">Clothes</option>
            <option value="accessory">Accessory</option>
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium">Photo</label>
          <input type="file" accept=".png,.jpg,.jpeg" onChange={(e) => setItemPhoto(e.target.files?.[0] ?? null)} className="mt-1 text-sm" />
        </div>
        <button type="submit" disabled={addingItem} className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50">
          Add
        </button>
      </form>

      <div>
        <h2 className="font-semibold">Uniforms &amp; Items</h2>
        <div className="mt-3 flex flex-col gap-4">
          {groups.map((group) => (
            <UniformRow key={group.uniform.id} group={group} onRenameUniform={renameUniform} onRenameItem={renameItem} />
          ))}
        </div>
      </div>
    </div>
  );
}

function UniformRow({
  group,
  onRenameUniform,
  onRenameItem,
}: {
  group: UniformGroup;
  onRenameUniform: (id: string, type: string, name: string) => void;
  onRenameItem: (id: string, type: string, size: string) => void;
}) {
  const [type, setType] = useState(group.uniform.uniformType);
  const [name, setName] = useState(group.uniform.uniformName ?? "");

  return (
    <div className="rounded-md border border-black/10 p-4">
      <div className="flex items-end gap-2">
        <input value={type} onChange={(e) => setType(e.target.value)} maxLength={3} className="w-20 rounded-md border border-black/15 px-2 py-1 text-sm font-semibold" />
        <input value={name} onChange={(e) => setName(e.target.value)} className="flex-1 rounded-md border border-black/15 px-2 py-1 text-sm" />
        <button onClick={() => onRenameUniform(group.uniform.id, type, name)} className="rounded-md border border-black/15 px-3 py-1 text-sm">
          Save
        </button>
      </div>
      <table className="mt-3 w-full text-left text-sm">
        <thead>
          <tr className="text-black/50">
            <th className="py-1">Item</th>
            <th className="py-1">Size Rule</th>
            <th className="py-1">Accessory</th>
            <th className="py-1"></th>
          </tr>
        </thead>
        <tbody>
          {group.items.map((item) => (
            <ItemRow key={item.id} item={item} onRename={onRenameItem} />
          ))}
        </tbody>
      </table>
    </div>
  );
}

function ItemRow({
  item,
  onRename,
}: {
  item: UniformGroup["items"][number];
  onRename: (id: string, type: string, size: string) => void;
}) {
  const [type, setType] = useState(item.clothesType);
  const [size, setSize] = useState(item.clothesSize ?? "");

  return (
    <tr className="border-t border-black/5">
      <td className="py-1">
        <input value={type} onChange={(e) => setType(e.target.value)} className="w-full rounded-md border border-black/15 px-2 py-1 text-sm" />
      </td>
      <td className="py-1">
        <input value={size} onChange={(e) => setSize(e.target.value)} className="w-40 rounded-md border border-black/15 px-2 py-1 text-sm" />
      </td>
      <td className="py-1">{item.accessory ? "Yes" : "No"}</td>
      <td className="py-1">
        <button onClick={() => onRename(item.id, type, size)} className="rounded-md border border-black/15 px-2 py-1 text-sm">
          Save
        </button>
      </td>
    </tr>
  );
}
