"use client";

import { useState } from "react";
import { apiPost, apiPut } from "@/lib/clientApi";
import type { NotificationSettingsResponse } from "@/lib/types";

export default function NotificationsTab({ initialData }: { initialData: NotificationSettingsResponse }) {
  const [enabled, setEnabled] = useState(initialData.pushEnabled);
  const [savingToggle, setSavingToggle] = useState(false);
  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");
  const [sending, setSending] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  async function handleSaveToggle(e: React.FormEvent) {
    e.preventDefault();
    setSavingToggle(true);
    try {
      await apiPut("/api/admin/system-settings/notifications", { enabled });
    } finally {
      setSavingToggle(false);
    }
  }

  async function handleSend(e: React.FormEvent) {
    e.preventDefault();
    if (!confirm("Hantar pengumuman ini kepada semua pengguna aktif sekarang?")) return;

    setSending(true);
    setMessage(null);
    try {
      const res = await apiPost("/api/admin/system-settings/notifications/announce", { title, body });
      const data = await res.json();
      setMessage({ kind: data.ok ? "ok" : "error", text: data.message ?? "Gagal menghantar." });
      if (data.ok) {
        setTitle("");
        setBody("");
      }
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setSending(false);
    }
  }

  return (
    <>
      <div className="shop-subtitle">Hidupkan/matikan pemberitahuan push, dan hantar pengumuman kepada pengguna.</div>
      <br />

      <div className="panel panel-default">
        <div className="panel-heading">
          <strong>Pemberitahuan Push</strong>
        </div>
        <div className="panel-body">
          <p>
            Status FCM:{" "}
            {initialData.fcmConfigured ? (
              <span className="label label-success">Dikonfigurasi</span>
            ) : (
              <>
                <span className="label label-default">Belum dikonfigurasi</span> &mdash; lihat{" "}
                <code>docs/push-notifications.md</code>.
              </>
            )}
          </p>

          <form onSubmit={handleSaveToggle} autoComplete="off">
            <div className="checkbox">
              <label>
                <input type="checkbox" checked={enabled} onChange={(e) => setEnabled(e.target.checked)} /> Aktifkan
                pemberitahuan push
              </label>
            </div>
            <p className="help-block">
              Apabila dimatikan, pesanan dan pengumuman masih direkod dalam inbox aplikasi, tetapi tiada push
              dihantar ke peranti.
            </p>
            <div className="subBtn">
              <input
                className="btn btn-default text-uppercase"
                type="submit"
                value={savingToggle ? "Menyimpan..." : "Simpan"}
                disabled={savingToggle}
              />
            </div>
          </form>
        </div>
      </div>

      <div className="panel panel-default">
        <div className="panel-heading">
          <strong>Hantar Pengumuman</strong>
        </div>
        <div className="panel-body">
          <p>
            Penerima: <strong>{initialData.activeUserCount}</strong> pengguna aktif, <strong>{initialData.deviceCount}</strong>{" "}
            peranti berdaftar.
          </p>

          <form onSubmit={handleSend} autoComplete="off">
            <div className="form-group">
              <label className="label_">Tajuk</label>
              <input
                className="form-control"
                type="text"
                maxLength={255}
                required
                value={title}
                onChange={(e) => setTitle(e.target.value)}
              />
            </div>

            <div className="form-group">
              <label className="label_">Mesej</label>
              <textarea
                className="form-control"
                rows={6}
                maxLength={1000}
                required
                value={body}
                onChange={(e) => setBody(e.target.value)}
              />
            </div>

            <div className="subBtn">
              <input
                className="btn btn-primary text-uppercase"
                type="submit"
                value={sending ? "Menghantar..." : "Hantar Pengumuman"}
                disabled={sending}
              />
            </div>
            <br />
            {message && (
              <p className={message.kind === "ok" ? "text-success" : "text-danger"}>{message.text}</p>
            )}
            <div className="alert alert-danger">
              <strong>PERHATIAN!</strong> Pengumuman akan dihantar serta-merta kepada semua pengguna aktif yang
              disenaraikan di atas. Sila semak sebelum menghantar.
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
