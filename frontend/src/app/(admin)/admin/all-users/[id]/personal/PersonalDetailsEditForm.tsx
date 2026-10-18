"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { apiUrl } from "@/lib/clientApi";
import type { AdminUserDetailResponse, DropdownOption } from "@/lib/types";

const KNOWN_RELIGIONS = ["ISLAM", "BUDDHISM", "HINDU", "CHRISTIANITY"];

export default function PersonalDetailsEditForm({
  userId,
  data,
}: {
  userId: string;
  data: AdminUserDetailResponse;
}) {
  const pd = data.personalDetails!;
  const [name, setName] = useState(pd.name);
  const [service, setService] = useState(pd.service ?? "");
  const [ketukanganType, setKetukanganType] = useState(pd.ketukanganType || 1);
  const [tred, setTred] = useState(pd.tred ?? "");
  const [pangkat, setPangkat] = useState(pd.pangkat ?? "");
  const [unit, setUnit] = useState(pd.unit ?? "");
  const [gender, setGender] = useState(pd.gender ?? "");
  const [telephoneNumber, setTelephoneNumber] = useState(pd.telephoneNumber);
  const [dutyStatus, setDutyStatus] = useState(pd.dutyStatus ?? "");
  const [otherReligion, setOtherReligion] = useState(
    pd.religion && !KNOWN_RELIGIONS.includes(pd.religion) ? pd.religion : "",
  );
  const [religion, setReligion] = useState(pd.religion ?? "");
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
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  const religionIsOther = religion !== "" && !KNOWN_RELIGIONS.includes(religion);
  const religionSelectValue = religionIsOther ? "" : religion || "ISLAM";

  const tredOptions =
    ketukanganType === 2
      ? [...data.dropdowns.tredRecruit, ...data.dropdowns.tredBoth]
      : [...data.dropdowns.tredOfficer, ...data.dropdowns.tredBoth];

  const [fetchedRankOptions, setFetchedRankOptions] = useState<DropdownOption[]>([]);
  const rankOptions = service ? fetchedRankOptions : [];

  useEffect(() => {
    if (!service) return;
    const params = new URLSearchParams({ service, tredType: String(ketukanganType) });
    fetch(apiUrl(`/api/admin/users/ranks?${params}`))
      .then((res) => res.json())
      .then((d) => setFetchedRankOptions(d.ranks ?? []))
      .catch(() => setFetchedRankOptions([]));
  }, [service, ketukanganType]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);
    try {
      const res = await fetch(apiUrl(`/api/admin/users/${userId}/personal`), {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          s_id: pd.sId,
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
          status_religion: religionIsOther ? "" : religion,
          status_religion_others: religionIsOther ? otherReligion : "",
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
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} autoComplete="off">
      <div className="form-group">
        <label className="label_">SERVICE ID</label>
        <input className="form-control" type="text" value={pd.sId} readOnly />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">NAME</label>
        <input
          className="form-control"
          type="text"
          value={name}
          onChange={(e) => setName(e.target.value.toUpperCase())}
          placeholder="Enter your name...."
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">SERVICES</label>
        <select className="form-control" value={service} onChange={(e) => setService(e.target.value)}>
          <option value="">Choose service</option>
          {data.dropdowns.services.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-12">
        <label className="radio-inline">
          <input
            type="radio"
            checked={ketukanganType === 1}
            onChange={() => setKetukanganType(1)}
          />
          Officer
        </label>
        <label className="radio-inline">
          <input
            type="radio"
            checked={ketukanganType === 2}
            onChange={() => setKetukanganType(2)}
          />
          Other Rank
        </label>
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">JAWATAN (POSITION)</label>
        <input
          className="form-control"
          type="text"
          value={position}
          onChange={(e) => setPosition(e.target.value)}
          placeholder="e.g. PEGAWAI TADBIR GRED W29"
          maxLength={255}
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">TRED</label>
        <select className="form-control" value={tred} onChange={(e) => setTred(e.target.value)}>
          <option value="">Choose tred</option>
          {tredOptions.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Unit</label>
        <select className="form-control" value={unit} onChange={(e) => setUnit(e.target.value)}>
          <option value="">Choose unit</option>
          {data.dropdowns.units.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Rank</label>
        <select className="form-control" value={pangkat} onChange={(e) => setPangkat(e.target.value)}>
          <option value="">Choose rank</option>
          {rankOptions.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">GENDER</label>
        <select className="form-control" value={gender} onChange={(e) => setGender(e.target.value)}>
          <option value="">Choose gender</option>
          {data.dropdowns.genders.map((opt) => (
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
          value={telephoneNumber}
          onChange={(e) => setTelephoneNumber(e.target.value)}
          placeholder="Enter your telephone number...."
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Duty Status</label>
        <select className="form-control" value={dutyStatus} onChange={(e) => setDutyStatus(e.target.value)}>
          <option value="">Select duty status</option>
          {data.dropdowns.dutyStatuses.map((opt) => (
            <option key={opt.id} value={opt.id}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-3">
        <label className="label_">RELIGION</label>
        <select
          className="form-control"
          value={religionSelectValue}
          onChange={(e) => {
            if (e.target.value) {
              setReligion(e.target.value);
              setOtherReligion("");
            } else {
              setReligion("");
            }
          }}
        >
          <option value="ISLAM">ISLAM</option>
          <option value="BUDDHISM">BUDDHISM</option>
          <option value="HINDU">HINDU</option>
          <option value="CHRISTIANITY">CHRISTIANITY</option>
          <option value="">OTHERS</option>
        </select>
      </div>
      <div className="form-group col-sm-3">
        <label className="label_">&nbsp;</label>
        <input
          className={`form-control${religionIsOther ? "" : " hide"}`}
          value={otherReligion}
          onChange={(e) => setOtherReligion(e.target.value.toUpperCase())}
          placeholder="Religion name"
        />
      </div>

      <div className="form-group col-sm-12">
        <label className="label_">Address</label>
        <input
          className="form-control"
          value={address1}
          onChange={(e) => setAddress1(e.target.value.toUpperCase())}
          placeholder="Address line1...."
        />
      </div>
      <div className="form-group col-sm-12">
        <input
          className="form-control"
          value={address2}
          onChange={(e) => setAddress2(e.target.value.toUpperCase())}
          placeholder="City...."
        />
      </div>
      <div className="form-group col-sm-6">
        <select className="form-control" value={address3} onChange={(e) => setAddress3(e.target.value)}>
          <option value="">--SELECT STATE--</option>
          {data.dropdowns.states.map((s) => (
            <option key={s.id} value={s.id}>
              {s.value}
            </option>
          ))}
        </select>
      </div>
      <div className="form-group col-sm-6">
        <input
          className="form-control"
          value={address4}
          onChange={(e) => setAddress4(e.target.value)}
          placeholder="Postcode...."
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Next of Kin name</label>
        <input
          className="form-control"
          type="text"
          value={namaWaris}
          onChange={(e) => setNamaWaris(e.target.value)}
          placeholder="Enter your next of kin name"
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">Next of Kin Contact Number</label>
        <input
          className="form-control"
          type="text"
          value={telephoneNumberWaris}
          onChange={(e) => setTelephoneNumberWaris(e.target.value)}
          placeholder="Enter your next of kin contact number"
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">NAME ON UNIFORM TAG</label>
        <input
          className="form-control"
          type="text"
          value={nameTag}
          onChange={(e) => setNameTag(e.target.value)}
          placeholder="Enter your uniform tag"
          maxLength={8}
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">OLD UNIT</label>
        <select className="form-control" value={unitLama} onChange={(e) => setUnitLama(e.target.value)}>
          <option value="">Choose old unit</option>
          {data.dropdowns.units.map((opt) => (
            <option key={opt.id} value={opt.value}>
              {opt.value}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">POSTING AUTHORITY</label>
        <input
          className="form-control"
          type="text"
          value={kemLama}
          onChange={(e) => setKemLama(e.target.value)}
          placeholder="Enter your posting authority"
        />
      </div>

      <div className="form-group col-sm-6">
        <label className="label_">SPECIAL AUTHORITY (IF AVAILABLE)</label>
        <input
          className="form-control"
          type="text"
          value={splLama}
          onChange={(e) => setSplLama(e.target.value)}
          placeholder="Enter your special authority"
        />
      </div>

      {message && (
        <div className="form-group col-sm-12">
          <span className={message.kind === "ok" ? "text-success" : "text-danger"}>{message.text}</span>
        </div>
      )}

      <div className="subBtn col-sm-12">
        <input className="btn btn-default" type="submit" value={saving ? "Saving..." : "SAVE"} disabled={saving} />
        <Link href="/admin/all-users" className="btn btn-default">
          {" "}
          CANCEL
        </Link>
      </div>

      <div className="clearfix"></div>
    </form>
  );
}
