"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { apiUrl } from "@/lib/clientApi";
import type { AdminOrderDetailResponse } from "@/lib/types";

const STATUS_BUTTONS = [
  { code: "5", className: "btn-info", icon: "fa-cogs", label: "Mark Processing" },
  { code: "3", className: "btn-success", icon: "fa-check", label: "Approve Order" },
  { code: "6", className: "btn-primary", icon: "fa-flag-checkered", label: "Mark Completed" },
  { code: "2", className: "btn-danger", icon: "fa-times", label: "Reject Order" },
  { code: "1", className: "btn-warning", icon: "fa-clock-o", label: "Mark Pending" },
];

function formatOrderedAt(value: string | null): string {
  if (!value) return "-";
  const d = new Date(value.replace(" ", "T"));
  if (isNaN(d.getTime())) return value;
  const day = String(d.getDate()).padStart(2, "0");
  const month = d.toLocaleDateString("en-GB", { month: "short" });
  const time = d.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", hour12: true });
  return `${day} ${month} ${d.getFullYear()} ${time}`;
}

function formatCollectionDate(value: string | null): string {
  if (!value) return "To be updated";
  const d = new Date(value.replace(" ", "T"));
  if (isNaN(d.getTime())) return value;
  const date = d.toLocaleDateString("en-GB", { day: "2-digit", month: "2-digit", year: "2-digit" });
  const time = d.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", hour12: true });
  const day = d.toLocaleDateString("en-US", { weekday: "short" });
  return `${date} ${time} (${day})`;
}

export default function OrderDetailForm({ data }: { data: AdminOrderDetailResponse }) {
  const router = useRouter();
  const { order } = data;

  const allowedStatuses = data.isOwnOrder
    ? data.allowedStatuses.filter((c) => c !== "3")
    : data.allowedStatuses;

  const [remarks, setRemarks] = useState(order.remarks ?? "");
  const [remarksInvalid, setRemarksInvalid] = useState(false);
  const [collectionDate, setCollectionDate] = useState(
    order.collectionDate ? order.collectionDate.slice(0, 10) : "",
  );
  const [collectionTime, setCollectionTime] = useState(
    order.collectionDate ? order.collectionDate.slice(11, 16) : "09:00",
  );
  const [approvedQuantities, setApprovedQuantities] = useState<Record<string, string>>(
    Object.fromEntries(data.items.map((item) => [item.id, String(item.approvedQuantity ?? item.quantity)])),
  );
  const [issueVouchers, setIssueVouchers] = useState<Record<string, string>>(
    Object.fromEntries(data.items.map((item) => [item.id, item.issueVoucher ?? ""])),
  );
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(null);

  const quantityLabel = data.canRecordIssueVouchers ? "Quantity Approved" : "Quantity Request";

  async function handleStatusSubmit(code: string) {
    if (code === "2" && remarks.trim() === "") {
      setRemarksInvalid(true);
      return;
    }
    setRemarksInvalid(false);
    setSaving(true);
    setMessage(null);

    try {
      const res = await fetch(apiUrl(`/api/admin/orders/${order.id}/status`), {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          status: code,
          remarks,
          collection_date: collectionDate || null,
          collection_time: collectionTime || null,
          approved_quantity: data.canApproveQuantities ? approvedQuantities : undefined,
          issue_voucher: data.canRecordIssueVouchers ? issueVouchers : undefined,
        }),
      });
      const result = await res.json();

      if (!res.ok) {
        setMessage({ kind: "error", text: result.message ?? "Could not update the order." });
        return;
      }

      setMessage({ kind: "ok", text: "Order status updated successfully." });
      router.refresh();
    } catch {
      setMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <div className="title">
        <i className="fa fa-folder-open" aria-hidden="true"></i> Uniform Order Detail
      </div>
      <hr />

      <div className="containerMain">
        <div className="content">
          <Link href="/admin/orders" className="btn btn-default">
            <i className="fa fa-arrow-left" aria-hidden="true"></i> Back to Orders
          </Link>
          <hr />

          <div className="order-card">
            <div className="order-card-header">
              <div>
                <div className="report-card-title">{order.uniformLabel}</div>
                <div className="order-card-meta">Order #{order.id}</div>
              </div>
              <span className={`status-badge status-${order.status}`}>{order.statusLabel}</span>
            </div>

            <div className="order-detail-layout">
              <div className="order-detail-main">
                <div className="order-summary-grid">
                  <div className="order-meta-block">
                    <div className="order-info-row">
                      <span className="order-info-label">Service ID</span>
                      <span className="order-info-value">{order.sId ?? "-"}</span>
                    </div>
                    <div className="order-info-row">
                      <span className="order-info-label">User</span>
                      <span className="order-info-value">{order.name ?? "N/A"}</span>
                    </div>
                    <div className="order-info-row">
                      <span className="order-info-label">Email</span>
                      <span className="order-info-value">{order.email ?? "N/A"}</span>
                    </div>
                    <div className="order-info-row">
                      <span className="order-info-label">Unit</span>
                      <span className="order-info-value">{order.unitName ?? "N/A"}</span>
                    </div>
                    <div className="order-info-row">
                      <span className="order-info-label">Ordered At</span>
                      <span className="order-info-value">{formatOrderedAt(order.createdAt)}</span>
                    </div>
                    <div className="order-info-row">
                      <span className="order-info-label">Collection Date</span>
                      <span className="order-info-value">{formatCollectionDate(order.collectionDate)}</span>
                    </div>
                  </div>
                </div>

                <div className="table-responsive">
                  <table className="table table-orders">
                    <thead>
                      <tr>
                        <th>Clothing Item</th>
                        <th>Size Ordered</th>
                        <th>{quantityLabel}</th>
                        {data.canApproveQuantities && !data.isOwnOrder && <th>Quantity Approved</th>}
                        {data.canRecordIssueVouchers && <th>Issue Voucher</th>}
                      </tr>
                    </thead>
                    <tbody>
                      {data.items.map((item) => (
                        <tr key={item.id}>
                          <td data-label="Clothing Item">{item.clothes}</td>
                          <td data-label="Size Ordered">{item.size}</td>
                          <td data-label={quantityLabel}>
                            {data.canRecordIssueVouchers ? item.approvedQuantity ?? item.quantity : item.quantity}
                          </td>
                          {data.canApproveQuantities && !data.isOwnOrder && (
                            <td data-label="Quantity Approved">
                              <input
                                type="number"
                                className="form-control input-sm"
                                style={{ maxWidth: 90 }}
                                min={0}
                                max={item.quantity}
                                step={1}
                                value={approvedQuantities[item.id] ?? ""}
                                onChange={(e) =>
                                  setApprovedQuantities((prev) => ({ ...prev, [item.id]: e.target.value }))
                                }
                                aria-label={`Quantity Approved: ${item.clothes}`}
                              />
                            </td>
                          )}
                          {data.canRecordIssueVouchers && (
                            <td data-label="Issue Voucher">
                              <input
                                type="text"
                                className="form-control input-sm"
                                style={{ maxWidth: 160 }}
                                maxLength={100}
                                value={issueVouchers[item.id] ?? ""}
                                onChange={(e) =>
                                  setIssueVouchers((prev) => ({ ...prev, [item.id]: e.target.value }))
                                }
                                aria-label={`Issue Voucher: ${item.clothes}`}
                              />
                            </td>
                          )}
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>

              <div className="order-detail-sidebar">
                <div className="order-actions-heading" style={{ paddingTop: 0, borderTop: 0 }}>
                  Review this order
                </div>

                <div className={`form-group${remarksInvalid ? " has-error" : ""}`}>
                  <label className="label_">Remarks</label>
                  <textarea
                    className={`form-control${remarksInvalid ? " field-invalid" : ""}`}
                    rows={4}
                    placeholder="Add approval or rejection remarks here"
                    value={remarks}
                    onChange={(e) => {
                      setRemarks(e.target.value);
                      if (e.target.value.trim() !== "") setRemarksInvalid(false);
                    }}
                  />
                  {remarksInvalid && (
                    <p className="field-error-text">
                      A rejected order must tell the member why. Please key in the remarks.
                    </p>
                  )}
                </div>

                <div className="form-group">
                  <label className="label_">Collection Date</label>
                  <div className="row">
                    <div className="col-xs-7">
                      <input
                        type="date"
                        className="form-control"
                        value={collectionDate}
                        onChange={(e) => setCollectionDate(e.target.value)}
                      />
                    </div>
                    <div className="col-xs-5">
                      <input
                        type="time"
                        className="form-control"
                        aria-label="Collection Time"
                        value={collectionTime}
                        onChange={(e) => setCollectionTime(e.target.value)}
                      />
                    </div>
                  </div>
                  <p className="help-block">
                    Leave empty to set it on approval: the time of approval, or 09:00 the next weekday if
                    approved after 17:00 or at the weekend.
                  </p>
                </div>

                <div className="order-actions-heading">Set status</div>

                {data.isOwnOrder && (
                  <div className="alert alert-warning" role="note">
                    <i className="fa fa-info-circle" aria-hidden="true"></i> This is your own order. Another
                    admin must approve it.
                  </div>
                )}

                {message && (
                  <p className={message.kind === "ok" ? "text-success" : "text-danger"}>{message.text}</p>
                )}

                <div className="order-admin-actions">
                  {STATUS_BUTTONS.filter((b) => allowedStatuses.includes(b.code)).map((b) => (
                    <button
                      key={b.code}
                      type="button"
                      disabled={saving}
                      className={`btn ${b.className} btn-block`}
                      onClick={() => handleStatusSubmit(b.code)}
                    >
                      <i className={`fa ${b.icon}`} aria-hidden="true"></i> {b.label}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>

          <div className="order-card">
            <div className="order-card-header">
              <div>
                <div className="report-card-title">KEW.PS-8</div>
                <div className="order-card-meta">Borang Permohonan Stok</div>
              </div>
              <a href={apiUrl(`/api/admin/orders/${order.id}/kew-ps8`)} target="_blank" rel="noreferrer" className="btn btn-brand">
                <i className="fa fa-download" aria-hidden="true"></i> Download KEW.PS-8
              </a>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
