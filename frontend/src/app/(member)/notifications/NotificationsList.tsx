"use client";

import { useState } from "react";
import type { NotificationsResponse } from "@/lib/types";

export default function NotificationsList({ initialData }: { initialData: NotificationsResponse }) {
  const [notifications, setNotifications] = useState(initialData.notifications);
  const [unreadCount, setUnreadCount] = useState(initialData.unread_count);
  const [marking, setMarking] = useState(false);

  async function markAllRead() {
    setMarking(true);
    try {
      const res = await fetch("/api/notifications/read", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({}),
      });
      const data = await res.json();
      setUnreadCount(data.unread_count ?? 0);
      setNotifications((prev) => prev.map((n) => ({ ...n, read: true })));
    } finally {
      setMarking(false);
    }
  }

  return (
    <div className="mt-4">
      <div className="mb-4 flex items-center justify-between">
        <span className="text-sm text-black/50">{unreadCount} unread</span>
        {unreadCount > 0 && (
          <button
            onClick={markAllRead}
            disabled={marking}
            className="rounded-md border border-black/15 px-3 py-1.5 text-sm hover:bg-black/5 disabled:opacity-50"
          >
            {marking ? "Marking..." : "Mark all as read"}
          </button>
        )}
      </div>

      {notifications.length === 0 ? (
        <p className="text-sm text-black/50">No notifications.</p>
      ) : (
        <ul className="flex flex-col gap-3">
          {notifications.map((n) => (
            <li
              key={n.id}
              className={`rounded-md border p-3 ${n.read ? "border-black/10" : "border-black/30 bg-black/5"}`}
            >
              <div className="font-medium">{n.title}</div>
              <div className="text-sm text-black/70">{n.body}</div>
              <div className="mt-1 text-xs text-black/40">{n.created_at}</div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
