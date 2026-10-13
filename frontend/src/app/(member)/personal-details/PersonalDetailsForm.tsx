"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import type { ClothingSizesResponse, DropdownOption, PersonalDetails, ProfileResponse } from "@/lib/types";

const EMPTY_DETAILS: PersonalDetails = {
  s_id: "",
  name: "",
  service: "",
  ketukangan_type: 1,
  tred: "",
  pangkat: "",
  unit: "",
  gender: "",
  telephone_number: "",
  duty_status: "",
  religion: "",
  address_line1: "",
  address_city: "",
  address_state: "",
  address_postcode: "",
  nama_waris: "",
  telephone_number_waris: "",
  name_tag: "",
  unit_lama: "",
  kem_lama: "",
  spl_lama: "",
};

export default function PersonalDetailsForm({
  initialTab,
  profile,
  clothingSizes,
}: {
  initialTab: "details" | "sizes";
  profile: ProfileResponse;
  clothingSizes: ClothingSizesResponse;
}) {
  const router = useRouter();
  const [tab, setTab] = useState<"details" | "sizes">(initialTab);

  function switchTab(next: "details" | "sizes") {
    setTab(next);
    router.replace(`/personal-details?tab=${next}`, { scroll: false });
  }

  return (
    <div className="rounded-lg border border-black/10 bg-white">
      <div className="flex border-b border-black/10">
        <button
          type="button"
          onClick={() => switchTab("details")}
          className={`px-4 py-3 text-sm font-medium ${
            tab === "details" ? "border-b-2 border-black" : "text-black/50"
          }`}
        >
          Personal Details
        </button>
        <button
          type="button"
          onClick={() => switchTab("sizes")}
          className={`px-4 py-3 text-sm font-medium ${
            tab === "sizes" ? "border-b-2 border-black" : "text-black/50"
          }`}
        >
          Clothing Sizes
        </button>
      </div>
      <div className="p-6">
        {tab === "details" ? (
          <DetailsTab profile={profile} />
        ) : (
          <SizesTab groups={clothingSizes.groups} />
        )}
      </div>
    </div>
  );
}

function DetailsTab({ profile }: { profile: ProfileResponse }) {
  const [form, setForm] = useState<PersonalDetails>(profile.personalDetails ?? EMPTY_DETAILS);
  const [position, setPosition] = useState(profile.position);
  const [status, setStatus] = useState<{ kind: "idle" | "ok" | "error"; message?: string }>({
    kind: "idle",
  });
  const [saving, setSaving] = useState(false);

  const tredOptions =
    Number(form.ketukangan_type) === 2 ? profile.dropdowns.tredRecruit : profile.dropdowns.tredOfficer;

  const [fetchedRankOptions, setFetchedRankOptions] = useState<DropdownOption[]>([]);
  const rankOptions = form.service ? fetchedRankOptions : [];

  useEffect(() => {
    if (!form.service) {
      return;
    }
    const params = new URLSearchParams({
      piliihAngkatan: form.service,
      ketukanganType: String(form.ketukangan_type),
    });
    fetch(`/api/profile/ranks?${params}`)
      .then((res) => res.json())
      .then((data) => setFetchedRankOptions(data.ranks ?? []))
      .catch(() => setFetchedRankOptions([]));
  }, [form.service, form.ketukangan_type]);

  function field(name: keyof PersonalDetails, value: string) {
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setStatus({ kind: "idle" });

    try {
      const res = await fetch("/api/profile", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: form.name,
          service: form.service,
          ketukangan_type: String(form.ketukangan_type),
          tred: form.tred,
          pangkat: form.pangkat,
          unit: form.unit,
          gender: form.gender,
          telephone_number: form.telephone_number,
          duty_status: form.duty_status,
          religion: form.religion,
          position,
          address_line1: form.address_line1,
          address_city: form.address_city,
          address_state: form.address_state,
          address_postcode: form.address_postcode,
          nama_waris: form.nama_waris,
          telephone_number_waris: form.telephone_number_waris,
          name_tag: form.name_tag,
          unit_lama: form.unit_lama,
          kem_lama: form.kem_lama,
          spl_lama: form.spl_lama,
        }),
      });
      const data = await res.json();

      if (!res.ok) {
        setStatus({ kind: "error", message: data.message ?? "Could not save your details." });
        return;
      }

      setForm(data.personalDetails);
      setPosition(data.position ?? "");
      setStatus({ kind: "ok", message: "Your details have been saved." });
    } catch {
      setStatus({ kind: "error", message: "Could not reach the server." });
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <TextField label="Service ID" value={form.s_id} readOnly />
      <TextField label="Full Name" value={form.name} onChange={(v) => field("name", v)} required />
      <SelectField
        label="Service"
        value={form.service ?? ""}
        onChange={(v) => field("service", v)}
        options={profile.dropdowns.services}
        required
      />
      <SelectField
        label="Officer / Recruit"
        value={String(form.ketukangan_type)}
        onChange={(v) => field("ketukangan_type", v)}
        options={[
          { id: "1", value: "Officer" },
          { id: "2", value: "Recruit" },
        ]}
        required
      />
      <SelectField
        label="Tred"
        value={form.tred ?? ""}
        onChange={(v) => field("tred", v)}
        options={tredOptions}
        required
      />
      <SelectField
        label="Rank"
        value={form.pangkat ?? ""}
        onChange={(v) => field("pangkat", v)}
        options={rankOptions}
        required
      />
      <SelectField
        label="Unit"
        value={form.unit ?? ""}
        onChange={(v) => field("unit", v)}
        options={profile.dropdowns.units}
        required
      />
      <SelectField
        label="Gender"
        value={form.gender ?? ""}
        onChange={(v) => field("gender", v)}
        options={profile.dropdowns.genders}
        required
      />
      <TextField
        label="Telephone Number"
        value={form.telephone_number}
        onChange={(v) => field("telephone_number", v)}
        required
      />
      <SelectField
        label="Duty Status"
        value={form.duty_status ?? ""}
        onChange={(v) => field("duty_status", v)}
        options={profile.dropdowns.dutyStatuses}
        required
      />
      <TextField label="Religion" value={form.religion ?? ""} onChange={(v) => field("religion", v)} />
      <TextField label="Position" value={position} onChange={setPosition} />
      <TextField
        label="Address Line 1"
        value={form.address_line1}
        onChange={(v) => field("address_line1", v)}
      />
      <TextField label="City" value={form.address_city} onChange={(v) => field("address_city", v)} />
      <SelectField
        label="State"
        value={form.address_state}
        onChange={(v) => field("address_state", v)}
        options={profile.dropdowns.states}
      />
      <TextField
        label="Postcode"
        value={form.address_postcode}
        onChange={(v) => field("address_postcode", v)}
      />
      <TextField
        label="Next of Kin Name"
        value={form.nama_waris}
        onChange={(v) => field("nama_waris", v)}
      />
      <TextField
        label="Next of Kin Contact"
        value={form.telephone_number_waris}
        onChange={(v) => field("telephone_number_waris", v)}
      />
      <TextField
        label="Name on Uniform Tag"
        value={form.name_tag}
        onChange={(v) => field("name_tag", v)}
        maxLength={8}
      />
      <TextField label="Old Unit" value={form.unit_lama} onChange={(v) => field("unit_lama", v)} />
      <TextField
        label="Posting Authority"
        value={form.kem_lama}
        onChange={(v) => field("kem_lama", v)}
      />
      <TextField
        label="Special Authority"
        value={form.spl_lama}
        onChange={(v) => field("spl_lama", v)}
      />

      <div className="col-span-full flex items-center gap-4">
        <button
          type="submit"
          disabled={saving}
          className="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-black/80 disabled:opacity-50"
        >
          {saving ? "Saving..." : "Save"}
        </button>
        {status.kind === "ok" && <span className="text-sm text-green-700">{status.message}</span>}
        {status.kind === "error" && <span className="text-sm text-red-600">{status.message}</span>}
      </div>
    </form>
  );
}

function SizesTab({ groups }: { groups: ClothingSizesResponse["groups"] }) {
  const initialValues: Record<string, string> = {};
  for (const group of groups) {
    for (const item of group.items) {
      initialValues[item.clothesId] = item.savedValue;
    }
  }

  const [values, setValues] = useState(initialValues);
  const [status, setStatus] = useState<{ kind: "idle" | "ok" | "error"; message?: string }>({
    kind: "idle",
  });
  const [saving, setSaving] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setStatus({ kind: "idle" });

    try {
      const res = await fetch("/api/profile/clothing-sizes", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ sizes: values }),
      });
      const data = await res.json();

      if (!res.ok) {
        setStatus({ kind: "error", message: data.message ?? "Could not save your sizes." });
        return;
      }

      setStatus({
        kind: "ok",
        message: data.saved > 0 ? "Your sizes have been saved." : "No new sizes to save.",
      });
    } catch {
      setStatus({ kind: "error", message: "Could not reach the server." });
    } finally {
      setSaving(false);
    }
  }

  if (groups.length === 0) {
    return <p className="text-sm text-black/50">No clothing items are set up yet.</p>;
  }

  return (
    <form onSubmit={handleSubmit}>
      <p className="mb-6 text-sm text-black/50">
        Set your own size for each clothing item. This is saved to your profile, separate from any
        specific order -- an item left blank is simply not saved.
      </p>
      {groups.map((group) => (
        <div key={group.uniform.id} className="mb-6 border-b border-black/10 pb-4 last:border-b-0">
          <div className="mb-3 font-semibold uppercase">
            {group.uniform.uniformType}
            {group.uniform.uniformName ? ` (${group.uniform.uniformName})` : ""}
          </div>
          <div className="flex flex-wrap gap-4">
            {group.items.map((item) => (
              <div key={item.clothesId} className="min-w-[220px] flex-1">
                <label className="block text-sm font-medium">{item.clothesType}</label>
                {item.sizeKind === "text" ? (
                  <input
                    type="text"
                    placeholder={item.placeholder ?? ""}
                    value={values[item.clothesId] ?? ""}
                    onChange={(e) =>
                      setValues((prev) => ({ ...prev, [item.clothesId]: e.target.value }))
                    }
                    className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
                  />
                ) : (
                  <select
                    value={values[item.clothesId] ?? ""}
                    onChange={(e) =>
                      setValues((prev) => ({ ...prev, [item.clothesId]: e.target.value }))
                    }
                    className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
                  >
                    <option value="">Choose your size....</option>
                    {item.sizeOptions.map((opt) => (
                      <option key={opt} value={opt}>
                        {opt}
                      </option>
                    ))}
                  </select>
                )}
              </div>
            ))}
          </div>
        </div>
      ))}
      <div className="flex items-center gap-4">
        <button
          type="submit"
          disabled={saving}
          className="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-black/80 disabled:opacity-50"
        >
          {saving ? "Saving..." : "Save"}
        </button>
        {status.kind === "ok" && <span className="text-sm text-green-700">{status.message}</span>}
        {status.kind === "error" && <span className="text-sm text-red-600">{status.message}</span>}
      </div>
    </form>
  );
}

function TextField({
  label,
  value,
  onChange,
  readOnly,
  required,
  maxLength,
}: {
  label: string;
  value: string;
  onChange?: (value: string) => void;
  readOnly?: boolean;
  required?: boolean;
  maxLength?: number;
}) {
  return (
    <div>
      <label className="block text-sm font-medium">{label}</label>
      <input
        type="text"
        value={value ?? ""}
        onChange={(e) => onChange?.(e.target.value)}
        readOnly={readOnly}
        required={required}
        maxLength={maxLength}
        className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm read-only:bg-black/5"
      />
    </div>
  );
}

function SelectField({
  label,
  value,
  onChange,
  options,
  required,
}: {
  label: string;
  value: string;
  onChange: (value: string) => void;
  options: { id: string; value: string }[];
  required?: boolean;
}) {
  return (
    <div>
      <label className="block text-sm font-medium">{label}</label>
      <select
        value={value ?? ""}
        onChange={(e) => onChange(e.target.value)}
        required={required}
        className="mt-1 w-full rounded-md border border-black/15 px-3 py-2 text-sm"
      >
        <option value="">-- Select --</option>
        {options.map((opt) => (
          <option key={opt.id} value={opt.id}>
            {opt.value}
          </option>
        ))}
      </select>
    </div>
  );
}
