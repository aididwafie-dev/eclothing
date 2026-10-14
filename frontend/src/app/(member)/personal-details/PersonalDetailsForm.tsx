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

const STATES = [
  "JOHOR",
  "W.P. KUALA LUMPUR",
  "W.P. LABUAN",
  "W.P. PUTRAJAYA",
  "KEDAH",
  "KELANTAN",
  "MELAKA",
  "NEGERI SEMBILAN",
  "PAHANG",
  "PERAK",
  "PERLIS",
  "PULAU PINANG",
  "SABAH",
  "SARAWAK",
  "SELANGOR",
  "TERENGGANU",
  "OTHERS",
];

const KNOWN_RELIGIONS = ["ISLAM", "BUDDHISM", "HINDU", "CHRISTIANITY"];

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
    <>
      <div className="title">
        <i className="fa fa-address-card" aria-hidden="true"></i> Personal Details
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <ul className="nav nav-tabs" role="tablist">
            <li role="presentation" className={tab === "details" ? "active" : ""}>
              <a onClick={() => switchTab("details")} role="tab" style={{ cursor: "pointer" }}>
                Personal Details
              </a>
            </li>
            <li role="presentation" className={tab === "sizes" ? "active" : ""}>
              <a onClick={() => switchTab("sizes")} role="tab" style={{ cursor: "pointer" }}>
                Clothing Sizes
              </a>
            </li>
          </ul>

          <div className="tab-content" style={{ paddingTop: 16 }}>
            <div className={`tab-pane ${tab === "details" ? "active" : ""}`}>
              <hr />
              <DetailsTab profile={profile} />
            </div>
            <div className={`tab-pane ${tab === "sizes" ? "active" : ""}`}>
              <hr />
              <div className="shop-subtitle">
                Set your own size for each clothing item. This is saved to your profile, separate from
                any specific order.
              </div>
              <br />
              <SizesTab groups={clothingSizes.groups} />
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

function DetailsTab({ profile }: { profile: ProfileResponse }) {
  const [form, setForm] = useState<PersonalDetails>(profile.personalDetails ?? EMPTY_DETAILS);
  const [position, setPosition] = useState(profile.position);
  const [otherReligion, setOtherReligion] = useState(
    profile.personalDetails?.religion && !KNOWN_RELIGIONS.includes(profile.personalDetails.religion)
      ? profile.personalDetails.religion
      : "",
  );
  const [status, setStatus] = useState<{ kind: "idle" | "ok" | "error"; message?: string }>({
    kind: "idle",
  });
  const [saving, setSaving] = useState(false);

  const religionIsOther = form.religion != null && !KNOWN_RELIGIONS.includes(form.religion) && form.religion !== "";
  const religionSelectValue = religionIsOther ? "" : form.religion ?? "ISLAM";

  const tredOptions =
    Number(form.ketukangan_type) === 2
      ? [...profile.dropdowns.tredRecruit, ...profile.dropdowns.tredBoth]
      : [...profile.dropdowns.tredOfficer, ...profile.dropdowns.tredBoth];

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

  function restore() {
    setForm(profile.personalDetails ?? EMPTY_DETAILS);
    setPosition(profile.position);
    setOtherReligion(
      profile.personalDetails?.religion && !KNOWN_RELIGIONS.includes(profile.personalDetails.religion)
        ? profile.personalDetails.religion
        : "",
    );
    setStatus({ kind: "ok", message: "Details restored." });
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
          religion: religionIsOther ? otherReligion : form.religion,
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
    <form onSubmit={handleSubmit} autoComplete="off">
      <div className="form-group col-sm-6">
        <label className="label_">Service ID</label>
        <input className="form-control" type="text" value={form.s_id} readOnly />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Name</label>
        <input
          className="form-control"
          type="text"
          value={form.name}
          onChange={(e) => field("name", e.target.value.toUpperCase())}
          placeholder="Enter your name"
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Service</label>
        <select className="form-control" value={form.service ?? ""} onChange={(e) => field("service", e.target.value)}>
          <option value="">Choose your service....</option>
          {profile.dropdowns.services.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-6">
        <label className="radio-inline">
          <input
            type="radio"
            checked={Number(form.ketukangan_type) === 1}
            onChange={() => field("ketukangan_type", "1")}
          />
          <label className="label_ text-uppercase">Officer</label>
        </label>
        <label className="radio-inline">
          <input
            type="radio"
            checked={Number(form.ketukangan_type) === 2}
            onChange={() => field("ketukangan_type", "2")}
          />
          <label className="label_ text-uppercase">Other Rank</label>
        </label>
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Position</label>
        <input
          className="form-control"
          type="text"
          value={position}
          onChange={(e) => setPosition(e.target.value)}
          placeholder="Enter your position"
          maxLength={255}
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Tred</label>
        <select className="form-control" value={form.tred ?? ""} onChange={(e) => field("tred", e.target.value)}>
          <option value="">Choose your tred....</option>
          {tredOptions.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Unit</label>
        <select className="form-control" value={form.unit ?? ""} onChange={(e) => field("unit", e.target.value)}>
          <option value="">Choose your unit....</option>
          {profile.dropdowns.units.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Rank</label>
        <select className="form-control" value={form.pangkat ?? ""} onChange={(e) => field("pangkat", e.target.value)}>
          <option value="">Choose your rank....</option>
          {rankOptions.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Gender</label>
        <select className="form-control" value={form.gender ?? ""} onChange={(e) => field("gender", e.target.value)}>
          <option value="">Choose your gender....</option>
          {profile.dropdowns.genders.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Telephone Number</label>
        <input
          className="form-control"
          type="text"
          value={form.telephone_number}
          onChange={(e) => field("telephone_number", e.target.value)}
          placeholder="Enter your telephone number"
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Duty Status</label>
        <select
          className="form-control"
          value={form.duty_status ?? ""}
          onChange={(e) => field("duty_status", e.target.value)}
        >
          <option value="">Choose your duty status....</option>
          {profile.dropdowns.dutyStatuses.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-3">
        <label className="label_">Religion</label>
        <select
          className="form-control"
          value={religionSelectValue}
          onChange={(e) => {
            if (e.target.value) {
              field("religion", e.target.value);
              setOtherReligion("");
            } else {
              field("religion", "");
            }
          }}
        >
          <option value="ISLAM">Islam</option>
          <option value="BUDDHISM">Buddhism</option>
          <option value="HINDU">Hindu</option>
          <option value="CHRISTIANITY">Christianity</option>
          <option value="">Others</option>
        </select>
      </div>
      <div className="form-group col-sm-3">
        <label className="label_">&nbsp;</label>
        <input
          className={`form-control${religionIsOther ? "" : " hide"}`}
          value={otherReligion}
          onChange={(e) => setOtherReligion(e.target.value.toUpperCase())}
          placeholder="Specify your religion"
        />
      </div>

      <div className="form-group col-sm-12">
        <label className="label_">Address</label>
        <input
          className="form-control"
          value={form.address_line1}
          onChange={(e) => field("address_line1", e.target.value.toUpperCase())}
          placeholder="Address line 1"
        />
      </div>
      <div className="form-group col-sm-12">
        <input
          className="form-control"
          value={form.address_city}
          onChange={(e) => field("address_city", e.target.value.toUpperCase())}
          placeholder="City"
        />
      </div>
      <div className="form-group col-sm-6">
        <select
          className="form-control"
          value={form.address_state}
          onChange={(e) => field("address_state", e.target.value)}
        >
          <option value="">Select state....</option>
          {STATES.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
      </div>
      <div className="form-group col-sm-6">
        <input
          className="form-control"
          value={form.address_postcode}
          onChange={(e) => field("address_postcode", e.target.value)}
          placeholder="Postcode"
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Next of Kin Name</label>
        <input
          className="form-control"
          type="text"
          value={form.nama_waris}
          onChange={(e) => field("nama_waris", e.target.value.toUpperCase())}
          placeholder="Enter next of kin's name"
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Next of Kin Contact Number</label>
        <input
          className="form-control"
          type="text"
          value={form.telephone_number_waris}
          onChange={(e) => field("telephone_number_waris", e.target.value)}
          placeholder="Enter next of kin's contact number"
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Name on Tag</label>
        <input
          className="form-control"
          type="text"
          value={form.name_tag}
          onChange={(e) => field("name_tag", e.target.value.toUpperCase())}
          placeholder="Enter name for uniform tag"
          required
          maxLength={8}
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Old Unit</label>
        <select
          className="form-control"
          value={form.unit_lama}
          onChange={(e) => field("unit_lama", e.target.value)}
        >
          <option value="">Choose your old unit....</option>
          {profile.dropdowns.units.map((opt) => (
            <option key={opt.id} value={opt.value}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Posting Authority</label>
        <input
          className="form-control"
          type="text"
          value={form.kem_lama}
          onChange={(e) => field("kem_lama", e.target.value)}
          placeholder="Enter posting authority"
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Special Authority</label>
        <input
          className="form-control"
          type="text"
          value={form.spl_lama}
          onChange={(e) => field("spl_lama", e.target.value)}
          placeholder="Enter special authority"
        />
      </div>

      <div className="clearfix"></div>
      <div className="subBtn text-center">
        <input className="btn btn-info" type="submit" value={saving ? "Saving..." : "Save"} disabled={saving} />
        <button type="button" className="btn btn-default" onClick={restore}>
          Restore
        </button>
      </div>
      {status.kind === "ok" && (
        <div className="form-group col-sm-12 text-center">
          <span className="text-success">{status.message}</span>
        </div>
      )}
      {status.kind === "error" && (
        <div className="form-group col-sm-12 text-center">
          <span className="text-danger">{status.message}</span>
        </div>
      )}
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
    return <div className="shop-subtitle">No clothing items have been set up yet.</div>;
  }

  return (
    <form onSubmit={handleSubmit} autoComplete="off">
      {groups.map((group) => (
        <div key={group.uniform.id} className="clothing-size-uniform">
          <div className="clothing-size-uniform-title">
            {group.uniform.uniformType}
            {group.uniform.uniformName ? ` (${group.uniform.uniformName})` : ""}
          </div>
          <div className="clothing-size-items">
            {group.items.map((item) => (
              <div key={item.clothesId} className="form-group clothing-size-item">
                <label className="label_">{item.clothesType}</label>
                {item.sizeKind === "text" ? (
                  <input
                    className="form-control"
                    type="text"
                    value={values[item.clothesId] ?? ""}
                    placeholder={item.placeholder ?? ""}
                    onChange={(e) =>
                      setValues((prev) => ({ ...prev, [item.clothesId]: e.target.value }))
                    }
                  />
                ) : (
                  <select
                    className="form-control"
                    value={values[item.clothesId] ?? ""}
                    onChange={(e) =>
                      setValues((prev) => ({ ...prev, [item.clothesId]: e.target.value }))
                    }
                  >
                    <option value="">Choose size....</option>
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

      <div className="clearfix"></div>
      <div className="subBtn text-center">
        <input className="btn btn-info" type="submit" value={saving ? "Saving..." : "Save"} disabled={saving} />
      </div>
      {status.kind === "ok" && (
        <div className="form-group col-sm-12 text-center">
          <span className="text-success">{status.message}</span>
        </div>
      )}
      {status.kind === "error" && (
        <div className="form-group col-sm-12 text-center">
          <span className="text-danger">{status.message}</span>
        </div>
      )}
    </form>
  );
}
