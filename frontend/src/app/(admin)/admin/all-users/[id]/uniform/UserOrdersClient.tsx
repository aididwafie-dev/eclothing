"use client";

import Link from "next/link";
import { useState } from "react";
import { apiDelete } from "@/lib/clientApi";
import type { AdminUserOrder } from "@/lib/types";

function formatDate(value: string | null) {
  if (!value) return "To be updated";
  const d = new Date(value.replace(" ", "T"));
  return Number.isNaN(d.getTime())
    ? value
    : d.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
}

export default function UserOrdersClient({ userId, initialOrders }: { userId: string; initialOrders: AdminUserOrder[] }) {
  const [orders, setOrders] = useState(initialOrders);

  async function handleDelete(order: AdminUserOrder) {
    if (!confirm("Are you sure you want to remove this order from the site permanently?")) return;
    const res = await apiDelete(`/api/admin/user-orders/${order.id}`);
    if (res.ok) {
      setOrders((prev) => prev.filter((o) => o.id !== order.id));
    }
  }

  if (orders.length === 0) {
    return (
      <div className="container containerMain">
        <div className="content">This user has not placed any uniform order yet.</div>
      </div>
    );
  }

  return (
    <>
      {orders.map((order) => (
        <div className="containerMain" key={order.id}>
          <div className="content">
            <div className="order-card-header">
              <div>
                <div className="report-card-title">Uniform: {order.uniform}</div>
                <div className="order-card-meta">Order #{order.id}</div>
              </div>
              <span className={`status-badge ${order.statusClass}`}>{order.statusLabel}</span>
            </div>
            <div className="orders-toolbar">
              <div>
                <Link href={`/admin/all-users/${userId}/uniform/${order.id}`}>
                  <i className="fa fa-edit" aria-hidden="true"></i> Edit
                </Link>{" "}
                |{" "}
                <Link href={`/admin/orders/${order.id}`}>
                  <i className="fa fa-eye" aria-hidden="true"></i> View Detail
                </Link>
              </div>
              <button type="button" className="btn btn-xs btn-danger" onClick={() => handleDelete(order)}>
                <span className="glyphicon glyphicon-trash"></span> Delete Order
              </button>
            </div>
            <hr />
            <div className="order-meta-block">
              <div className="order-info-row">
                <span className="order-info-label">Collection Date</span>
                <span className="order-info-value">{formatDate(order.collectionDate)}</span>
              </div>
              <div className="order-info-row">
                <span className="order-info-label">Remarks</span>
                <span className="order-info-value">{order.remarks !== "" ? order.remarks : "No remarks yet."}</span>
              </div>
            </div>
            <div className="table-responsive">
              <table className="table table-orders">
                <thead>
                  <tr>
                    <th>Clothing Item</th>
                    <th>Size Ordered</th>
                  </tr>
                </thead>
                <tbody>
                  {order.lines.map((line) => (
                    <tr key={line.id}>
                      <td data-label="Clothing Item">{line.clothes}</td>
                      <td data-label="Size Ordered">{line.size}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      ))}
    </>
  );
}
