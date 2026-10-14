"use client";

import { useState } from "react";
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
        setItemPhoto(null);
        await refresh();
      }
    } finally {
      setAddingItem(false);
    }
  }

  async function renameUniform(id: string, uniformType: string, uniformName: string, photo: File | null) {
    const formData = new FormData();
    formData.append("uniformType", uniformType);
    formData.append("uniformName", uniformName);
    if (photo) formData.append("photo", photo);
    const res = await fetch(`/api/admin/system-settings/uniforms/${id}`, { method: "POST", body: formData });
    const data = await res.json();
    setMessage({ kind: res.ok ? "ok" : "error", text: data.message });
    if (res.ok) await refresh();
  }

  async function renameItem(id: string, clothesType: string, clothesSize: string, photo: File | null) {
    const formData = new FormData();
    formData.append("clothesType", clothesType);
    formData.append("clothesSize", clothesSize);
    if (photo) formData.append("photo", photo);
    const res = await fetch(`/api/admin/system-settings/uniform-items/${id}`, { method: "POST", body: formData });
    const data = await res.json();
    setMessage({ kind: res.ok ? "ok" : "error", text: data.message });
    if (res.ok) await refresh();
  }

  return (
    <>
      <div className="shop-subtitle">Kemaskini gambar dan maklumat bagi setiap uniform untuk paparan dalam shopping cart.</div>
      <br />

      <div className="uniform-add-panel">
        <div className="uniform-add-title">
          <i className="fa fa-plus-circle" aria-hidden="true"></i> Tambah Kategori Uniform Baharu
        </div>
        <div className="uniform-add-hint">
          Kategori baharu akan terus muncul dalam tab <strong>Skala Pakaian &amp; Kelayakan</strong> selepas
          disimpan.
        </div>

        <form onSubmit={handleAddUniform} autoComplete="off">
          <div className="row">
            <div className="col-sm-3">
              <div className="form-group">
                <label className="label_">Kod Jenis Uniform</label>
                <input
                  className="form-control"
                  type="text"
                  maxLength={3}
                  placeholder="cth: 3A"
                  required
                  value={newType}
                  onChange={(e) => setNewType(e.target.value)}
                />
                <p className="help-block">Maksimum 3 aksara.</p>
              </div>
            </div>
            <div className="col-sm-5">
              <div className="form-group">
                <label className="label_">Nama Uniform</label>
                <input
                  className="form-control"
                  type="text"
                  placeholder="cth: BAJU KERJA"
                  value={newName}
                  onChange={(e) => setNewName(e.target.value)}
                />
              </div>
            </div>
            <div className="col-sm-4">
              <div className="form-group">
                <label className="label_">Gambar Uniform (PNG/JPG)</label>
                <input
                  className="form-control"
                  type="file"
                  accept="image/png,image/jpeg"
                  onChange={(e) => setNewPhoto(e.target.files?.[0] ?? null)}
                />
              </div>
            </div>
          </div>

          <div className="subBtn" style={{ textAlign: "left" }}>
            <input
              className="btn btn-primary text-uppercase"
              type="submit"
              value={addingUniform ? "Menambah..." : "Tambah Uniform"}
              disabled={addingUniform}
            />
          </div>
        </form>
      </div>

      {message && <p className={message.kind === "ok" ? "text-success" : "text-danger"}>{message.text}</p>}

      {groups.length > 0 && (
        <div className="form-group">
          <label className="label_">Pilih Jenis Uniform</label>
          <select className="form-control" value={itemUniformId} onChange={(e) => setItemUniformId(e.target.value)}>
            {groups.map((g) => (
              <option key={g.uniform.id} value={g.uniform.id}>
                {g.uniform.uniformType}
                {g.uniform.uniformName ? ` (${g.uniform.uniformName})` : ""}
              </option>
            ))}
          </select>
        </div>
      )}

      {groups.length > 0 ? (
        groups.map((group) => <UniformPanel key={group.uniform.id} group={group} onRenameUniform={renameUniform} onRenameItem={renameItem} />)
      ) : (
        <div className="alert alert-info">Tiada uniform dijumpai.</div>
      )}

      {groups.length > 0 && (
        <div className="uniform-add-panel">
          <div className="uniform-add-title">
            <i className="fa fa-plus-circle" aria-hidden="true"></i> Tambah Pakaian / Aksesori
          </div>
          <div className="uniform-add-hint">
            Item baharu ditambah kepada uniform yang dipilih di atas:{" "}
            <strong>
              {groups.find((g) => g.uniform.id === itemUniformId)?.uniform.uniformType}
              {groups.find((g) => g.uniform.id === itemUniformId)?.uniform.uniformName
                ? ` (${groups.find((g) => g.uniform.id === itemUniformId)?.uniform.uniformName})`
                : ""}
            </strong>
          </div>

          <form onSubmit={handleAddItem} autoComplete="off">
            <div className="row">
              <div className="col-sm-4">
                <div className="form-group">
                  <label className="label_">Nama Item</label>
                  <input
                    className="form-control"
                    type="text"
                    placeholder="cth: Inner Shirt"
                    required
                    value={itemType}
                    onChange={(e) => setItemType(e.target.value)}
                  />
                </div>
              </div>
              <div className="col-sm-2">
                <div className="form-group">
                  <label className="label_">Jenis</label>
                  <select className="form-control" value={itemKind} onChange={(e) => setItemKind(e.target.value as "clothes" | "accessory")}>
                    <option value="clothes">Pakaian</option>
                    <option value="accessory">Aksesori</option>
                  </select>
                </div>
              </div>
              <div className="col-sm-3">
                <div className="form-group">
                  <label className="label_">Saiz</label>
                  <input
                    className="form-control"
                    type="text"
                    placeholder="cth: FIX atau S | M | L"
                    value={itemSize}
                    onChange={(e) => setItemSize(e.target.value)}
                  />
                  <p className="help-block">
                    Kosong = tiada saiz (checkbox sahaja). <code>FIX</code> = saiz tetap. Senarai pilihan
                    dipisahkan dengan <code>|</code>, julat dengan <code>-</code>.
                  </p>
                </div>
              </div>
              <div className="col-sm-3">
                <div className="form-group">
                  <label className="label_">Gambar Item (PNG/JPG)</label>
                  <input
                    className="form-control"
                    type="file"
                    accept="image/png,image/jpeg"
                    onChange={(e) => setItemPhoto(e.target.files?.[0] ?? null)}
                  />
                </div>
              </div>
            </div>

            <div className="subBtn" style={{ textAlign: "left" }}>
              <input
                className="btn btn-primary text-uppercase"
                type="submit"
                value={addingItem ? "Menambah..." : "Tambah Item"}
                disabled={addingItem}
              />
            </div>
          </form>
        </div>
      )}
    </>
  );
}

function UniformPanel({
  group,
  onRenameUniform,
  onRenameItem,
}: {
  group: UniformGroup;
  onRenameUniform: (id: string, type: string, name: string, photo: File | null) => void;
  onRenameItem: (id: string, type: string, size: string, photo: File | null) => void;
}) {
  const [type, setType] = useState(group.uniform.uniformType);
  const [name, setName] = useState(group.uniform.uniformName ?? "");
  const [photo, setPhoto] = useState<File | null>(null);

  const uniformLabel = `${group.uniform.uniformType}${group.uniform.uniformName ? ` (${group.uniform.uniformName})` : ""}`;

  return (
    <div className="uniform-settings-panel active">
      <div className="title" style={{ marginTop: 0 }}>
        {uniformLabel}
      </div>
      <div className="table-responsive">
        <table className="table table-orders">
          <thead>
            <tr>
              <th>Jenis Uniform</th>
              <th>Nama Uniform</th>
              <th>Gambar Semasa</th>
              <th>Muat Naik Gambar Baru</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td data-label="Jenis Uniform">
                <input className="form-control" value={type} maxLength={3} onChange={(e) => setType(e.target.value)} />
              </td>
              <td data-label="Nama Uniform">
                <input className="form-control" value={name} onChange={(e) => setName(e.target.value)} />
              </td>
              <td data-label="Gambar Semasa">
                {group.uniform.photoUrl && (
                  <img src={group.uniform.photoUrl} className="img-responsive" alt="uniform" style={{ maxHeight: 90 }} />
                )}
              </td>
              <td data-label="Muat Naik Gambar Baru">
                <input
                  className="form-control"
                  type="file"
                  accept="image/png,image/jpeg"
                  onChange={(e) => setPhoto(e.target.files?.[0] ?? null)}
                />
                <button
                  type="button"
                  className="btn btn-sm btn-primary"
                  style={{ marginTop: 8 }}
                  onClick={() => onRenameUniform(group.uniform.id, type, name, photo)}
                >
                  Simpan
                </button>
              </td>
            </tr>
            <tr>
              <td colSpan={4}>
                <div className="shop-subtitle" style={{ marginBottom: 12 }}>
                  Pakaian &amp; Aksesori
                </div>
                {group.items.length > 0 ? (
                  <div className="table-responsive">
                    <table className="table table-bordered" style={{ marginBottom: 0 }}>
                      <thead>
                        <tr>
                          <th>Item Name</th>
                          <th>Saiz</th>
                          <th>Current Image</th>
                          <th>Upload New Image</th>
                        </tr>
                      </thead>
                      <tbody>
                        {group.items.map((item) => (
                          <ItemRow key={item.id} item={item} onRename={onRenameItem} />
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <div className="text-muted">No clothes or accessories found for this uniform.</div>
                )}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
}

function ItemRow({
  item,
  onRename,
}: {
  item: UniformGroup["items"][number];
  onRename: (id: string, type: string, size: string, photo: File | null) => void;
}) {
  const [type, setType] = useState(item.clothesType);
  const [size, setSize] = useState(item.clothesSize ?? "");
  const [photo, setPhoto] = useState<File | null>(null);

  return (
    <tr>
      <td data-label="Item Name">
        <span
          title={item.accessory ? "Accessory" : "Clothes"}
          aria-label={item.accessory ? "Accessory" : "Clothes"}
          className={`item-icon ${item.accessory ? "item-icon-accessory" : "item-icon-clothes"}`}
        >
          <i className={`fa ${item.accessory ? "fa-diamond" : "fa-shopping-bag"}`} aria-hidden="true"></i>
        </span>{" "}
        <input
          className="form-control"
          style={{ display: "inline-block", width: "calc(100% - 34px)" }}
          value={type}
          onChange={(e) => setType(e.target.value)}
          aria-label="Item Name"
        />
      </td>
      <td data-label="Saiz">
        <input className="form-control" value={size} onChange={(e) => setSize(e.target.value)} aria-label={`Saiz: ${item.clothesType}`} />
      </td>
      <td data-label="Current Image">
        {item.photoUrl ? (
          <img src={item.photoUrl} className="img-responsive" alt="item" style={{ maxHeight: 72 }} />
        ) : (
          <span className="text-muted">No image uploaded</span>
        )}
      </td>
      <td data-label="Upload New Image">
        <input className="form-control" type="file" accept="image/png,image/jpeg" onChange={(e) => setPhoto(e.target.files?.[0] ?? null)} />
        <button type="button" className="btn btn-sm btn-primary" style={{ marginTop: 8 }} onClick={() => onRename(item.id, type, size, photo)}>
          Simpan
        </button>
      </td>
    </tr>
  );
}
