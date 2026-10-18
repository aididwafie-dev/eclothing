"use client";

import { useState } from "react";
import { apiPost, apiUrl } from "@/lib/clientApi";
import type { ScaleResponse } from "@/lib/types";

export default function ScaleTab({ initialData }: { initialData: ScaleResponse }) {
  const [data, setData] = useState(initialData);
  const [rankId, setRankId] = useState(initialData.selectedRankId ?? "");
  const [loading, setLoading] = useState(false);
  const [activeUniformId, setActiveUniformId] = useState(initialData.uniforms[0]?.uniform.id ?? "");

  async function changeRank(id: string) {
    setRankId(id);
    setLoading(true);
    try {
      const res = await fetch(apiUrl(`/api/admin/system-settings/scale?pangkatId=${id}`));
      const next: ScaleResponse = await res.json();
      setData(next);
      setActiveUniformId(next.uniforms[0]?.uniform.id ?? "");
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

  const hasRanks = data.ranks.officer.length > 0 || data.ranks.nonOfficer.length > 0;

  return (
    <>
      <div className="shop-subtitle">
        Tetapkan bilangan helai/pasang yang dibenarkan bagi setiap pangkat <strong>AIR FORCE</strong>. Pilih
        pangkat, isi bilangan untuk setiap pakaian dan aksesori, kemudian simpan.
      </div>
      <br />

      {!data.storageReady && (
        <div className="alert alert-warning">
          Jadual <code>uniform_scales</code> belum wujud. Jalankan <code>php artisan migrate</code> untuk
          mengaktifkan tetapan ini.
        </div>
      )}

      {!hasRanks ? (
        <div className="alert alert-info">Tiada pangkat AIR FORCE dijumpai.</div>
      ) : (
        <>
          <div className="scale-legend">
            <span className="scale-legend-item">
              <span className="scale-chip scale-chip-unset">Kosong</span> Tiada had &mdash; ahli boleh memilih
              item ini seperti biasa.
            </span>
            <span className="scale-legend-item">
              <span className="scale-chip scale-chip-blocked">0</span> Tidak layak &mdash; item disembunyikan
              semasa membuat pesanan.
            </span>
            <span className="scale-legend-item">
              <span className="scale-chip scale-chip-set">1+</span> Had maksimum bilangan helai/pasang.
            </span>
          </div>

          <div className="scale-autosave">
            <div className="form-group">
              <label className="label_">Pilih Pangkat (AIR FORCE)</label>
              <select className="form-control" value={rankId} onChange={(e) => changeRank(e.target.value)}>
                {data.ranks.officer.length > 0 && (
                  <optgroup label="Pegawai (Officer)">
                    {data.ranks.officer.map((r) => (
                      <option key={r.id} value={r.id}>
                        {r.value}
                      </option>
                    ))}
                  </optgroup>
                )}
                {data.ranks.nonOfficer.length > 0 && (
                  <optgroup label="Bukan Pegawai (Non-Officer)">
                    {data.ranks.nonOfficer.map((r) => (
                      <option key={r.id} value={r.id}>
                        {r.value}
                      </option>
                    ))}
                  </optgroup>
                )}
              </select>
              <p className="help-block">Menukar pangkat akan memuat semula skala bagi pangkat tersebut.</p>
            </div>

            {!data.hiddenStorageReady && (
              <div className="alert alert-warning">
                Jadual <code>uniform_rank_hidden</code> belum wujud. Jalankan{" "}
                <code>php artisan migrate</code> untuk mengaktifkan sembunyi uniform.
              </div>
            )}

            {loading ? (
              <p className="text-muted">Loading...</p>
            ) : data.uniforms.length > 0 ? (
              <>
                <div className="scale-visibility">
                  <div className="scale-visibility-head">
                    <div className="scale-visibility-title">
                      <i className="fa fa-eye-slash" aria-hidden="true"></i> Sembunyikan Uniform dari Troli
                      Pesanan
                    </div>
                    <div className="scale-visibility-hint">
                      Tanda = uniform tidak dipaparkan semasa ahli pangkat ini membuat pesanan. Perubahan
                      disimpan automatik.
                    </div>
                  </div>
                  <div className="scale-visibility-grid">
                    {data.uniforms.map((group) => (
                      <label key={group.uniform.id} className={`scale-visibility-item${group.hidden ? " is-hidden" : ""}`}>
                        <input
                          type="checkbox"
                          checked={group.hidden}
                          onChange={(e) => toggleHidden(group.uniform.id, e.target.checked)}
                        />
                        <span className="scale-visibility-name">{group.uniform.label}</span>
                      </label>
                    ))}
                  </div>
                </div>

                <ul className="nav nav-pills scale-uniform-tabs" role="tablist" style={{ marginBottom: 16 }}>
                  {data.uniforms.map((group) => (
                    <li key={group.uniform.id} role="presentation" className={group.uniform.id === activeUniformId ? "active" : ""}>
                      <a onClick={() => setActiveUniformId(group.uniform.id)} role="tab" style={{ cursor: "pointer" }}>
                        <i
                          className="fa fa-eye-slash"
                          aria-hidden="true"
                          title="Disembunyikan dari troli"
                          style={{ visibility: group.hidden ? "visible" : "hidden" }}
                        ></i>{" "}
                        {group.uniform.label}
                      </a>
                    </li>
                  ))}
                </ul>

                <div className="tab-content">
                  {data.uniforms.map((group) => (
                    <div key={group.uniform.id} role="tabpanel" className={`tab-pane${group.uniform.id === activeUniformId ? " active" : ""}`}>
                      <div className={`scale-group${group.hidden ? " is-uniform-hidden" : ""}`}>
                        <div className="scale-group-title">
                          <i className="fa fa-shirtsinbulk" aria-hidden="true"></i> {group.uniform.label}{" "}
                          {group.hidden && <span className="scale-group-hidden-tag">Disembunyikan dari troli</span>}
                        </div>
                        <div className="table-responsive">
                          <table className="table table-orders">
                            <thead>
                              <tr>
                                <th>Item</th>
                                <th>Jenis</th>
                                <th style={{ width: 190 }}>Bilangan Dibenarkan</th>
                              </tr>
                            </thead>
                            <tbody>
                              {group.items.map((item) => (
                                <tr key={item.id}>
                                  <td data-label="Item">
                                    <span
                                      title={item.accessory ? "Aksesori" : "Pakaian"}
                                      aria-label={item.accessory ? "Aksesori" : "Pakaian"}
                                      className={`item-icon ${item.accessory ? "item-icon-accessory" : "item-icon-clothes"}`}
                                    >
                                      <i className={`fa ${item.accessory ? "fa-diamond" : "fa-shopping-bag"}`} aria-hidden="true"></i>
                                    </span>{" "}
                                    {item.clothesType}
                                  </td>
                                  <td data-label="Jenis">{item.accessory ? "Aksesori" : "Pakaian"}</td>
                                  <td data-label="Bilangan Dibenarkan">
                                    <ScaleInput disabled={group.hidden} item={item} onSave={saveItem} />
                                  </td>
                                </tr>
                              ))}
                              {group.items.length === 0 && (
                                <tr>
                                  <td colSpan={3} className="text-muted">
                                    Tiada pakaian atau aksesori lagi. Tambah item di tab{" "}
                                    <strong>Tetapan Pakaian</strong>, kemudian tetapkan skala di sini.
                                  </td>
                                </tr>
                              )}
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </>
            ) : (
              <div className="alert alert-info">Tiada uniform dijumpai.</div>
            )}

            <div className="scale-autosave-note">
              <i className="fa fa-check-circle" aria-hidden="true"></i> Semua perubahan disimpan secara
              automatik &mdash; tiada butang Simpan diperlukan.
            </div>
          </div>
        </>
      )}
    </>
  );
}

type ScaleKind = "unset" | "blocked" | "set";

function scaleKind(value: string): ScaleKind {
  const trimmed = value.trim();
  if (trimmed === "") return "unset";
  return parseInt(trimmed, 10) === 0 ? "blocked" : "set";
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
  const kind = scaleKind(value);

  return (
    <input
      type="number"
      min={0}
      max={999}
      step={1}
      disabled={disabled}
      value={value}
      placeholder="Tiada had"
      onChange={(e) => setValue(e.target.value)}
      onBlur={() => onSave(item.id, value)}
      className={`form-control scale-input is-scale-${kind}`}
      aria-label={`Bilangan dibenarkan`}
    />
  );
}
