"use client";

import { useEffect, useState } from "react";
import type { AdminUserDetailResponse, DropdownOption } from "@/lib/types";

const RELIGIONS = ["ISLAM", "BUDDHISM", "HINDU", "CHRISTIANITY", "OTHERS"];
const STATES = [
  "JOHOR", "W.P. KUALA LUMPUR", "W.P. LABUAN", "W.P. PUTRAJAYA", "KEDAH", "KELANTAN",
  "MELAKA", "NEGERI SEMBILAN", "PAHANG", "PERAK", "PERLIS", "PULAU PINANG", "SABAH",
  "SARAWAK", "SELANGOR", "TERENGGANU", "OTHERS",
];

export default function PersonalDetailsEditForm({
  userId,
  data,
}: {
  userId: string;
  data: AdminUserDetailResponse;
}) {
  const pd = data.personalDetails!;
  const sId = pd.sId;
  const [name, setName] = useState(pd.name);
  const [service, setService] = useState(pd.service ?? "");
  const [ketukanganType, setKetukanganType] = useState(pd.ketukanganType);
  const [tred, setTred] = useState(pd.tred ?? "");
  const [pangkat, setPangkat] = useState(pd.pangkat ?? "");
  const [unit, setUnit] = useState(pd.unit ?? "");
  const [gender, setGender] = useState(pd.gender ?? "");
  const [telephoneNumber, setTelephoneNumber] = useState(pd.telephoneNumber);
  const [dutyStatus, setDutyStatus] = useState(pd.dutyStatus ?? "");
  const [religionKnown, setReligionKnown] = useState(RELIGIONS.includes(pd.religion ?? "") ? (pd.religion ?? "") : "OTHERS");
  const [religionOther, setReligionOther] = useState(RELIGIONS.includes(pd.religion ?? "") ? "" : (pd.religion ?? ""));
  const [address1, setAddress1] = useState(pd.address[0] ?? "");
  const [address2, setAddress2] = useState(pd.address[1] ?? "");
  const [address3, setAddress3] = useState(pd.address[2] ?? "");
  const [address4, setAddress4] = useState(pd.address[3] ?? "");
  const [namaWaris, setNamaWaris] = useState(pd.namaWaris);
  const [telephoneNumberWaris, setTelephoneNumberWaris] = useState(pd.telephoneNumberWaris);
  const [nameTag, setNameTag] = useState(pd.nameTag);
  const [unitLama, setUnitLama] = useState(pd.unitLama);
  const [kemLama, setKemLama] = useState(pd.kemLama);
  const [splLama, setSplLama] = useState(pd.splLama);
  const [position, setPosition] = useState(data.position);

  const [fetchedRankOptions, setFetchedRankOptions] = useState<DropdownOption[]>([]);
  const rankOptions = service ? fetchedRankOptions : [];

  useEffect(() => {
    if (!service) return;
    const params = new URLSearchParams({ service, tredType: String(ketukanganType) });
    fetch(`/api/admin/users/ranks?${params}`)
      .then((res) => res.json())
      .then((d) => setFetchedRankOptions(d.ranks ?? []))
      .catch(() => setFetchedRankOptions([]));
  }, [service, ketukanganType]);

  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);
    try {
      const res = await fetch(`/api/admin/users/${userId}/personal`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          s_id: sId,
          name,
          piliih_angkatan: service,
          ketukangans_type: ketukanganType,
          ketukangans_officer: ketukanganType === 1 ? tred : null,
          ketukangans_recruit: ketukanganType === 2 ? tred : null,
          pangkat,
          unit,
          jantina: gender,
          telephone_number: telephoneNumber,
          address1,
          address2,
          address3,
          address4,
          nama_waris: namaWaris,
          tele_number_waris: telephoneNumberWaris,
          status_penggunaan: dutyStatus,
          unit_lama: unitLama,
          name_tag: nameTag,
          status_religion: religionKnown !== "OTHERS" ? religionKnown : null,
          status_religion_others: religionKnown === "OTHERS" ? religionOther : null,
          kem_lama: kemLama,
          spl_lama: splLama,
          position,
        }),
      });
      const result = await res.json();
      if (!res.ok) {
        setMessage({ kind: "error", text: result.message ?? "Could not save." });
        return;
      }
      setMessage({ kind: "ok", text: result.message });
    } finally {
      setSaving(false);
    }
  }

  const tredOptions = ketukanganType === 2 ? data.dropdowns.tredRecruit : data.dropdowns.tredOfficer;

  return (
    <form onSubmit={handleSubmit} className="rounded-lg border border-black/10 bg-white p-6">
      <h1 className="text-xl font-semibold">Edit Personal Details</h1>
      <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Text label="Service ID" value={sId} readOnly />
        <Text label="Full Name" value={name} onChange={setName} required />
        <Select label="Service" value={service} onChange={setService} options={data.dropdowns.services} required />
        <Select
          label="Officer / Recruit"
          value={String(ketukanganType)}
          onChange={(v) => setKetukanganType(Number(v))}
          options={[{ id: "1", value: "Officer" }, { id: "2", value: "Recruit" }]}
          required
        />
        <Select label="Tred" value={tred} onChange={setTred} options={tredOptions} required />
        <Select label="Rank" value={pangkat} onChange={setPangkat} options={rankOptions} required />
        <Select label="Unit" value={unit} onChange={setUnit} options={data.dropdowns.units} required />
        <Select label="Gender" value={gender} onChange={setGender} options={data.dropdowns.genders} required />
        <Text label="Telephone Number" value={telephoneNumber} onChange={setTelephoneNumber} required />
        <Select label="Duty Status" value={dutyStatus} onChange={setDutyStatus} options={data.dropdowns.dutyStatuses} required />
        <Select
          label="Religion"
          value={religionKnown}
          onChange={setReligionKnown}
          options={RELIGIONS.map((r) => ({ id: r, value: r }))}
        />
        {religionKnown === "OTHERS" && <Text label="Religion (other)" value={religionOther} onChange={setReligionOther} />}
        <Text label="Position" value={position} onChange={setPosition} />
        <Text label="Address Line 1" value={address1} onChange={setAddress1} />
        <Text label="City" value={address2} onChange={setAddress2} />
        <Select label="State" value={address3} onChange={setAddress3} options={STATES.map((s) => ({ id: s, value: s }))} />
        <Text label="Postcode" value={address4} onChange={setAddress4} />
        <Text label="Next of Kin Name" value={namaWaris} onChange={setNamaWaris} />
        <Text label="Next of Kin Contact" value={telephoneNumberWaris} onChange={setTelephoneNumberWaris} />
        <Text label="Name on Uniform Tag" value={nameTag} onChange={setNameTag} maxLength={8} />
        <Text label="Old Unit" value={unitLama} onChange={setUnitLama} />
        <Text label="Posting Authority" value={kemLama} onChange={setKemLama} />
        <Text label="Special Authority" value={splLama} onChange={setSplLama} />
      </div>
      <div className="mt-6 flex items-center gap-4">
        <button
          type="submit"
          disabled={saving}
          className="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
        >
          {saving ? "Saving..." : "Save"}
        </button>
        {message && (
          <span className={message.kind === "ok" ? "text-sm text-green-700" : "text-sm text-red-600"}>
            {message.text}
          </span>
        )}
      </div>
    </form>
  );
}

function Text({
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

function Select({
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
