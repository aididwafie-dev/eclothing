"use client";

import type { AdminIdentity, AdminIdentityOptions } from "@/lib/types";

/**
 * The Pegawai Pelulus identity block shared by viewDetails_before_add.blade.php
 * (Add New Admin) and edit_adminDetails.blade.php -- service ID, rank,
 * jawatan, access role and base.
 */
export default function AdminIdentityFields({
  value,
  onChange,
  options,
  labels,
}: {
  value: AdminIdentity;
  onChange: (value: AdminIdentity) => void;
  options: AdminIdentityOptions;
  labels: { sId: string; sIdPlaceholder: string; pangkat: string; jawatan: string; jawatanPlaceholder: string };
}) {
  const set = (key: keyof AdminIdentity) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) =>
    onChange({ ...value, [key]: e.target.value });

  return (
    <>
      <div className="form-group">
        <label className="label_">{labels.sId}</label>
        <input
          className="form-control"
          type="text"
          maxLength={255}
          placeholder={labels.sIdPlaceholder}
          value={value.sId}
          onChange={set("sId")}
        />
      </div>
      <div className="form-group">
        <label className="label_">{labels.pangkat}</label>
        <select className="form-control" value={value.pangkatId} onChange={set("pangkatId")}>
          <option value="">-- Choose Rank (Optional) --</option>
          {options.pangkats.map((p) => (
            <option key={p.id} value={p.id}>
              {p.value}
            </option>
          ))}
        </select>
      </div>
      <div className="form-group">
        <label className="label_">{labels.jawatan}</label>
        <input
          className="form-control"
          type="text"
          maxLength={255}
          placeholder={labels.jawatanPlaceholder}
          value={value.jawatan}
          onChange={set("jawatan")}
        />
      </div>
      <div className="form-group">
        <label className="label_">Akses (Access):</label>
        <select className="form-control" value={value.role} onChange={set("role")}>
          {options.roles.map((r) => (
            <option key={r.id} value={r.id}>
              {r.value}
            </option>
          ))}
        </select>
        <p className="help-block">
          &quot;Uniform Orders only&quot; limits the account to the order queue, where it can set Processing and
          Completed.
        </p>
      </div>
      <div className="form-group">
        <label className="label_">Base:</label>
        <select className="form-control" value={value.baseId} onChange={set("baseId")}>
          <option value="">-- No base (sees every unit) --</option>
          {options.bases.map((b) => (
            <option key={b.id} value={b.id}>
              {b.value}
            </option>
          ))}
        </select>
        <p className="help-block">Scopes the Unit filter on the Uniform Orders list to this base&apos;s units.</p>
      </div>
    </>
  );
}
