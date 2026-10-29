"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { apiUrl } from "@/lib/clientApi";
import type { AdminUniform } from "@/lib/types";

/**
 * addUniform.blade.php / uniform_editName.blade.php. Saves through the
 * System Settings uniform endpoints, which already handle the photo upload.
 */
export default function UniformForm({ uniform }: { uniform?: AdminUniform }) {
  const router = useRouter();
  const [uniformType, setUniformType] = useState(uniform?.uniformType ?? "");
  const [uniformName, setUniformName] = useState(uniform?.uniformName ?? "");
  const [photo, setPhoto] = useState<File | null>(null);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);

    const form = new FormData();
    form.append("uniformType", uniformType);
    form.append("uniformName", uniformName);
    if (photo) form.append("photo", photo);

    try {
      const url = uniform ? `/api/admin/system-settings/uniforms/${uniform.id}` : "/api/admin/system-settings/uniforms";
      const res = await fetch(apiUrl(url), { method: "POST", body: form });
      const data = await res.json();
      if (!res.ok) {
        setError(data.message ?? "Could not save the uniform.");
        return;
      }
      router.push("/admin/uniforms");
      router.refresh();
    } catch {
      setError("Could not reach the server.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <form autoComplete="off" onSubmit={handleSubmit}>
      <div className="form-group col-md-3">
        <label className="label_">Uniform type</label>
        <input
          type="text"
          className="form-control text-uppercase"
          required
          maxLength={uniform ? undefined : 2}
          value={uniformType}
          onChange={(e) => setUniformType(e.target.value)}
        />
      </div>
      <div className="form-group col-md-9">
        <label className="label_">Uniform name</label>
        <input type="text" className="form-control" value={uniformName} onChange={(e) => setUniformName(e.target.value)} />
      </div>
      <div className="form-group col-md-12">
        <label className="label_">Sample photo</label>
        <input
          className="form-control"
          type="file"
          accept=".jpg,.png"
          onChange={(e) => setPhoto(e.target.files?.[0] ?? null)}
        />
        <span className="text-muted">
          Please upload only .JPG or .PNG files. The images have to be of correct resolution. We recommend you download{" "}
          <a href="/front_end/images/uniforms/2.jpg" download>
            this sample image
          </a>{" "}
          and modify it in Photoshop.
        </span>
        {uniform && <img className="img img-thumbnail" src={uniform.photoPath} alt={uniform.uniformType} />}
      </div>

      {error && <p className="text-danger col-md-12">{error}</p>}

      <div className="text-center">
        <br />
        <button className="btn btn-primary" type="submit" disabled={saving}>
          {saving ? "Saving..." : uniform ? "SAVE CHANGES" : "SAVE UNIFORM"}
        </button>{" "}
        <Link href="/admin/uniforms" className="btn btn-default">
          {" "}
          CANCEL
        </Link>
      </div>
    </form>
  );
}
