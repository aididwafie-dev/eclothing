"use client";

import { useState } from "react";
import { apiPost } from "@/lib/clientApi";
import type { NotificationsResponse } from "@/lib/types";

export default function NotificationsList({ initialData }: { initialData: NotificationsResponse }) {
  const [notifications, setNotifications] = useState(initialData.notifications);
  const [unreadCount, setUnreadCount] = useState(initialData.unread_count);
  const [marking, setMarking] = useState(false);

  async function markAllRead() {
    setMarking(true);
    try {
      const res = await apiPost("/api/notifications/read");
      const data = await res.json();
      setUnreadCount(data.unread_count ?? 0);
      setNotifications((prev) => prev.map((n) => ({ ...n, read: true })));
    } finally {
      setMarking(false);
    }
  }

  return (
    <>
      <div className="shop-subtitle" style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
        <span>{unreadCount} unread</span>
        {unreadCount > 0 && (
          <button type="button" className="btn btn-default btn-sm" onClick={markAllRead} disabled={marking}>
            {marking ? "Marking..." : "Mark all as read"}
          </button>
        )}
      </div>
      <br />

      {notifications.length === 0 ? (
        <div className="notif-empty">No notifications.</div>
      ) : (
        <div className="notif-dropdown-list" style={{ maxHeight: "none" }}>
          {notifications.map((n) => (
            <div key={n.id} className={`notif-item${n.read ? "" : " notif-unread"}`}>
              <div className="notif-item-title">{n.title}</div>
              <div className="notif-item-body">{n.body}</div>
              <div className="notif-item-time">{n.created_at}</div>
            </div>
          ))}
        </div>
      )}
    </>
  );
}
