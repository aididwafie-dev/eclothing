"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { apiUrl } from "@/lib/clientApi";
import type { DropdownOption } from "@/lib/types";

export type FilterField = "uniformId" | "unitId" | "slug";

/**
 * The select*_report.blade.php "Generate Report" forms: pick a uniform
 * and/or unit (and, for the cloth report, a cloth of that uniform), then GO
 * reloads the same page with them in the query string.
 */
export default function ReportFilterForm({
  heading,
  fields,
  uniforms,
  units,
  unitLabel = "Select Unit",
  basePath,
}: {
  heading: string;
  fields: FilterField[];
  uniforms: DropdownOption[];
  units: DropdownOption[];
  unitLabel?: string;
  basePath: string;
}) {
  const router = useRouter();
  const [values, setValues] = useState<Record<FilterField, string>>({ uniformId: "", unitId: "", slug: "" });
  const [clothes, setClothes] = useState<DropdownOption[] | null>(null);
  const [loadingClothes, setLoadingClothes] = useState(false);
  const wantsCloth = fields.includes("slug");

  useEffect(() => {
    if (!wantsCloth || !values.uniformId) {
      setClothes(null);
      return;
    }
    setLoadingClothes(true);
    void fetch(apiUrl(`/api/admin/reports/clothes?uniformId=${encodeURIComponent(values.uniformId)}`))
      .then((res) => res.json())
      .then((data) => setClothes(data.clothes ?? []))
      .catch(() => setClothes([]))
      .finally(() => setLoadingClothes(false));
  }, [wantsCloth, values.uniformId]);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const query = new URLSearchParams();
    for (const f of fields) query.set(f, values[f]);
    router.push(`${basePath}?${query}`);
  }

  const set = (f: FilterField) => (e: React.ChangeEvent<HTMLSelectElement>) =>
    setValues((v) => ({ ...v, [f]: e.target.value, ...(f === "uniformId" ? { slug: "" } : {}) }));

  return (
    <>
      <i className="fa fa-filter" aria-hidden="true"></i> <b>{heading}</b>
      <hr />
      <form autoComplete="off" onSubmit={handleSubmit}>
        {fields.includes("uniformId") && (
          <div className="form-group">
            <label className="label_">Select Uniform Type</label>
            <select required className="form-control" value={values.uniformId} onChange={set("uniformId")}>
              <option value="">Choose uniform type....</option>
              {uniforms.map((u) => (
                <option key={u.id} value={u.id}>
                  {u.value}
                </option>
              ))}
            </select>
          </div>
        )}
        {wantsCloth && loadingClothes && (
          <div style={{ color: "var(--brand)" }}>
            <i className="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
            <span style={{ fontSize: "125%" }}>Loading clothes....</span>
          </div>
        )}
        {wantsCloth && clothes && !loadingClothes && (
          <div className="form-group">
            <label className="label_">Select Cloth Type</label>
            <select required className="form-control" value={values.slug} onChange={set("slug")}>
              <option value="">Choose cloth type....</option>
              {clothes.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.value}
                </option>
              ))}
            </select>
          </div>
        )}
        {fields.includes("unitId") && (
          <div className="form-group">
            <label className="label_">{unitLabel}</label>
            <select required className="form-control" value={values.unitId} onChange={set("unitId")}>
              <option value="">Choose unit</option>
              {units.map((u) => (
                <option key={u.id} value={u.id}>
                  {u.value}
                </option>
              ))}
            </select>
          </div>
        )}
        <div className="subBtn">
          <input className="btn btn-default" type="submit" value="GO" disabled={wantsCloth && loadingClothes} />
        </div>
      </form>
    </>
  );
}
