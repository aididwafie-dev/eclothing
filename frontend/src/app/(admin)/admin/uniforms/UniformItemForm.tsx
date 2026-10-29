"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import CheckboxMultiSelect from "@/components/CheckboxMultiSelect";
import { apiPost, apiPut } from "@/lib/clientApi";
import type { AdminUniformItem, DropdownOption, UniformItemOptions } from "@/lib/types";

type Filters = Pick<AdminUniformItem, "jantina" | "pangkat" | "ketukangan" | "religion">;

/**
 * add_clothes / editUniform_clothes.blade.php (multi-select filters) and
 * add_accessory / editAccessory.blade.php (one value per filter, blank for
 * "all"). Both write the same comma-separated uniform_clothes columns.
 */
export default function UniformItemForm({
  uniformId,
  accessory,
  item,
  options,
}: {
  uniformId: string;
  accessory: boolean;
  item?: AdminUniformItem;
  options: UniformItemOptions;
}) {
  const router = useRouter();
  const [clothesType, setClothesType] = useState(item?.clothesType ?? "");
  const [clothesSize, setClothesSize] = useState(item?.clothesSize ?? "");
  const [filters, setFilters] = useState<Filters>({
    jantina: item?.jantina ?? [],
    pangkat: item?.pangkat ?? [],
    ketukangan: item?.ketukangan ?? [],
    religion: item?.religion ?? [],
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const listHref = `/admin/uniforms/${uniformId}/${accessory ? "accessories" : "clothes"}`;
  const religionOptions: DropdownOption[] = options.religions.map((r) => ({ id: r, value: r }));
  const setFilter = (key: keyof Filters) => (value: string[]) => setFilters((f) => ({ ...f, [key]: value }));

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      const body = { uniformId, accessory, clothesType, clothesSize, ...filters };
      const res = item
        ? await apiPut(`/api/admin/uniform-clothes/${item.id}`, body)
        : await apiPost("/api/admin/uniform-clothes", body);
      const data = await res.json();
      if (!res.ok) {
        setError(data.message ?? "Could not save.");
        return;
      }
      router.push(listHref);
      router.refresh();
    } catch {
      setError("Could not reach the server.");
    } finally {
      setSaving(false);
    }
  }

  function filterField(key: keyof Filters, label: string, opts: DropdownOption[], allLabel: string, selectAll: string) {
    return (
      <div className="form-group col-sm-6">
        <label className="label_">{label}</label>
        {accessory ? (
          <select
            className="form-control"
            value={filters[key][0] ?? ""}
            onChange={(e) => setFilter(key)(e.target.value ? [e.target.value] : [])}
          >
            <option value="">{allLabel}</option>
            {opts.map((o) => (
              <option key={o.id} value={o.id}>
                {o.value}
              </option>
            ))}
          </select>
        ) : (
          <CheckboxMultiSelect options={opts} value={filters[key]} onChange={setFilter(key)} selectAllLabel={selectAll} />
        )}
      </div>
    );
  }

  return (
    <form autoComplete="off" onSubmit={handleSubmit}>
      <div className="row">
        <div className="form-group col-sm-12">
          <label className="label_">{accessory ? "Accessory name:" : item ? "Cloth:" : "Cloth type:"}</label>
          <input
            type="text"
            className="form-control"
            required
            value={clothesType}
            onChange={(e) => setClothesType(e.target.value)}
          />
          {!accessory && !item && "For accessories, please add under 'Accessories' option"}
        </div>
        <div className="form-group col-sm-12">
          <label className="label_">{accessory ? "Accessory options:" : item ? "Size:" : "Cloth size:"}</label>
          <input
            type="text"
            className="form-control"
            required={!accessory}
            value={clothesSize}
            onChange={(e) => setClothesSize(e.target.value.toUpperCase())}
          />
          {accessory && <small>For Yes / No selection checkbox leave this box blank.</small>}
        </div>
        {filterField("jantina", "Gender:", options.jantinas, "All genders", "[For both]")}
        {filterField("pangkat", "Rank:", options.pangkats, "All ranks", "[For all ranks]")}
        {filterField("ketukangan", "Tred:", options.ketukangans, "All treds", "[For all treds]")}
        {filterField("religion", "Religion:", religionOptions, "All religions", "[For all religions]")}
        {!accessory && <div className="col-sm-12">Leave the four boxes blank to not filter and show to all.</div>}

        {error && <p className="text-danger col-sm-12">{error}</p>}

        <div className="text-center col-sm-12">
          <br />
          <button className="btn btn-primary" type="submit" disabled={saving}>
            {saving ? "Saving..." : !accessory && !item ? "SAVE CLOTH" : "SAVE CHANGES"}
          </button>{" "}
          <Link href={listHref} className="btn btn-default">
            {" "}
            CANCEL
          </Link>
        </div>
      </div>
    </form>
  );
}
