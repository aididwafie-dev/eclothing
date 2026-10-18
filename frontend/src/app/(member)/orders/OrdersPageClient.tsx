"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { Fragment, useMemo, useState } from "react";
import { apiPost, apiUrl } from "@/lib/clientApi";
import type { MemberOrder } from "@/lib/types";

const MONTH_NAMES = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December",
];

const STATUS_OPTIONS = [
  { key: "pending", label: "Pending" },
  { key: "processing", label: "Processing" },
  { key: "approved", label: "Approved" },
  { key: "completed", label: "Completed" },
  { key: "rejected", label: "Rejected" },
  { key: "expired", label: "Expired" },
];

function formatDateTime(value: string | null, withSeconds?: boolean): string {
  if (!value) return "-";
  const d = new Date(value.replace(" ", "T"));
  if (isNaN(d.getTime())) return value;
  const date = d.toLocaleDateString("en-GB", { day: "2-digit", month: "2-digit", year: "2-digit" });
  const time = d.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", hour12: true });
  return withSeconds ? `${date} ${time}` : `${date} ${time}`;
}

export default function OrdersPageClient({ orders }: { orders: MemberOrder[] }) {
  const router = useRouter();
  const [search, setSearch] = useState("");
  const [month, setMonth] = useState("all");
  const [year, setYear] = useState("all");
  const [status, setStatus] = useState("all");
  const [openRow, setOpenRow] = useState<string | null>(null);
  const [mailSending, setMailSending] = useState(false);
  const [mailMessage, setMailMessage] = useState<string | null>(null);
  const [editingId, setEditingId] = useState<string | null>(null);

  const hasOrders = orders.length > 0;

  const years = useMemo(() => {
    const set = new Set<number>();
    for (const o of orders) {
      if (o.createdAt) {
        const y = new Date(o.createdAt.replace(" ", "T")).getFullYear();
        if (!isNaN(y)) set.add(y);
      }
    }
    return Array.from(set).sort((a, b) => b - a);
  }, [orders]);

  const searching = search.trim() !== "";

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return orders.filter((o) => {
      if (term) {
        const haystack = [
          `#${o.id}`,
          o.uniformName ?? "",
          o.uniformType,
          ...o.items.map((i) => i.clothes),
        ]
          .join(" ")
          .toLowerCase();
        return haystack.includes(term);
      }
      if (status !== "all" && o.status !== status) return false;
      if (o.createdAt) {
        const d = new Date(o.createdAt.replace(" ", "T"));
        if (month !== "all" && String(d.getMonth() + 1) !== month) return false;
        if (year !== "all" && String(d.getFullYear()) !== year) return false;
      }
      return true;
    });
  }, [orders, search, status, month, year]);

  async function handleSendMail() {
    setMailSending(true);
    setMailMessage(null);
    try {
      const res = await apiPost("/api/orders/email-details");
      const data = await res.json();
      setMailMessage(data.message ?? (res.ok ? "Order details have been emailed to you." : "Could not send the email."));
    } catch {
      setMailMessage("Could not reach the server.");
    } finally {
      setMailSending(false);
    }
  }

  async function handleEdit(order: MemberOrder) {
    setEditingId(order.id);
    try {
      const res = await fetch(apiUrl("/api/cart/load-from-order"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ orderId: order.id }),
      });
      const data = await res.json();
      if (!res.ok) {
        alert(data.message ?? "This order could not be loaded for editing.");
        return;
      }
      router.push(`/cart?uniform=${order.uniformsId}`);
    } catch {
      alert("Could not reach the server.");
    } finally {
      setEditingId(null);
    }
  }

  return (
    <>
      <div className="title">
        <i className="fa fa-shopping-bag" aria-hidden="true"></i> My Orders
      </div>
      <hr />

      <div className="containerMain">
        <div className="content table_center">
          <div className="orders-toolbar">
            <Link href="/cart" className="btn btn-brand">
              <i className="fa fa-plus" aria-hidden="true"></i> New Order
            </Link>
            {hasOrders && (
              <button type="button" className="btn btn-brand" onClick={handleSendMail} disabled={mailSending}>
                <i className="fa fa-envelope" aria-hidden="true"></i> {mailSending ? "Sending..." : "Send Mail"}
              </button>
            )}
          </div>

          {hasOrders && (
            <>
              <hr />
              <div className="shop-subtitle">Expand an order to see its full details and items.</div>
              <br />

              <form className="orders-search" role="search" onSubmit={(e) => e.preventDefault()}>
                <div className="orders-search-field">
                  <i className="fa fa-search" aria-hidden="true"></i>
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Search your orders...."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    autoComplete="off"
                  />
                </div>
                <div className="orders-filter-field">
                  <label className="orders-filter-label">Month</label>
                  <select
                    className="form-control"
                    value={month}
                    disabled={searching}
                    onChange={(e) => setMonth(e.target.value)}
                  >
                    <option value="all">All Months</option>
                    {MONTH_NAMES.map((name, i) => (
                      <option key={name} value={String(i + 1)}>
                        {name}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="orders-filter-field">
                  <label className="orders-filter-label">Year</label>
                  <select
                    className="form-control"
                    value={year}
                    disabled={searching}
                    onChange={(e) => setYear(e.target.value)}
                  >
                    <option value="all">All Years</option>
                    {years.map((y) => (
                      <option key={y} value={String(y)}>
                        {y}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="orders-filter-field">
                  <label className="orders-filter-label">Status</label>
                  <select className="form-control" value={status} onChange={(e) => setStatus(e.target.value)}>
                    <option value="all">All Statuses</option>
                    {STATUS_OPTIONS.map((s) => (
                      <option key={s.key} value={s.key}>
                        {s.label}
                      </option>
                    ))}
                  </select>
                </div>
                {searching && (
                  <button type="button" className="btn btn-default" onClick={() => setSearch("")}>
                    <i className="fa fa-times" aria-hidden="true"></i> Clear
                  </button>
                )}
              </form>
              {mailMessage && <p className="text-success">{mailMessage}</p>}
              {searching && (
                <div className="orders-search-result">
                  Showing search results for <strong>{search}</strong>
                </div>
              )}
            </>
          )}

          {filtered.length > 0 ? (
            <div className="table-responsive">
              <table className="table table-orders table-orders-member">
                <thead>
                  <tr>
                    <th>Order</th>
                    <th>Uniform</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Collection Date</th>
                    <th>Last Updated</th>
                    <th>
                      <span className="sr-only">Actions</span>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {filtered.map((order) => {
                    const isOpen = openRow === order.id;
                    const uniformLabel = order.uniformName ?? order.uniformType;
                    const remarks = (order.remarks ?? "").trim();
                    return (
                      <Fragment key={order.id}>
                        <tr
                          className={`order-row${isOpen ? " is-open" : ""}`}
                          onClick={() => setOpenRow(isOpen ? null : order.id)}
                        >
                          <td data-label="Order">
                            <button type="button" className="order-row-toggle" aria-expanded={isOpen}>
                              <i className="fa fa-chevron-right order-row-caret" aria-hidden="true"></i>
                              <span>#{order.id}</span>
                            </button>
                          </td>
                          <td data-label="Uniform">{uniformLabel}</td>
                          <td data-label="Items">{order.itemCount}</td>
                          <td data-label="Status">
                            <span className={`status-badge status-${order.status}`}>{order.statusLabel}</span>
                            {remarks !== "" && (
                              <i className="fa fa-comment-o order-row-remarks" aria-hidden="true" title="Has remarks"></i>
                            )}
                          </td>
                          <td data-label="Collection Date">
                            {order.collectionDate ? (
                              formatDateTime(order.collectionDate)
                            ) : (
                              <span className="text-muted">To be updated</span>
                            )}
                          </td>
                          <td data-label="Last Updated">{formatDateTime(order.updatedAt)}</td>
                          <td data-label="Actions" className="order-row-action">
                            <div className="order-row-buttons" onClick={(e) => e.stopPropagation()}>
                              {order.editable && (
                                <button
                                  type="button"
                                  className="btn btn-brand btn-sm"
                                  disabled={editingId === order.id}
                                  onClick={() => handleEdit(order)}
                                >
                                  <i className="fa fa-pencil" aria-hidden="true"></i>{" "}
                                  {editingId === order.id ? "Loading..." : "Edit"}
                                </button>
                              )}
                              <a
                                href={apiUrl(`/api/orders/${order.id}/kew-ps8`)}
                                target="_blank"
                                rel="noreferrer"
                                className="btn btn-default btn-sm"
                              >
                                <i className="fa fa-file-text-o" aria-hidden="true"></i> KEW.PS-8
                              </a>
                            </div>
                          </td>
                        </tr>
                        <tr className={`order-detail-row${isOpen ? " is-open" : ""}`}>
                          <td colSpan={7}>
                            <div className="order-detail-inner">
                              <dl className="order-detail-meta">
                                <div>
                                  <dt>Ordered</dt>
                                  <dd>{formatDateTime(order.createdAt)}</dd>
                                </div>
                                <div>
                                  <dt>Last Updated</dt>
                                  <dd>{formatDateTime(order.updatedAt)}</dd>
                                </div>
                                <div>
                                  <dt>Collection Date</dt>
                                  <dd>{order.collectionDate ? formatDateTime(order.collectionDate) : "To be updated"}</dd>
                                </div>
                                <div className="is-wide">
                                  <dt>Remarks</dt>
                                  <dd>{remarks !== "" ? remarks : "No remarks"}</dd>
                                </div>
                              </dl>

                              <div className="order-detail-items-title">Items Ordered</div>
                              <ul className="order-detail-items">
                                {order.items.length > 0 ? (
                                  order.items.map((item, i) => (
                                    <li key={i}>
                                      <span className="order-detail-item-name">{item.clothes}</span>
                                      <span className="order-detail-item-size">Size {item.size}</span>
                                    </li>
                                  ))
                                ) : (
                                  <li className="is-empty">No items on this order.</li>
                                )}
                              </ul>
                            </div>
                          </td>
                        </tr>
                      </Fragment>
                    );
                  })}
                </tbody>
              </table>
            </div>
          ) : hasOrders ? (
            <p className="text-muted">No orders match your search or filters.</p>
          ) : (
            "You have not placed any orders yet."
          )}
        </div>
      </div>
    </>
  );
}
