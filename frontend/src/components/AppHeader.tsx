"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { apiPost } from "@/lib/clientApi";
import { useSidebar } from "./AppShell";

type NotificationItem = {
  id: number;
  title: string;
  body: string;
  created_at: string;
  read: boolean;
};

/**
 * The shared topbar from static-layout/header.blade.php: hamburger,
 * site title, language toggle placeholder, search button, and the
 * notification bell (badge on load, full list + mark-as-read on open,
 * polled every 30s) -- same markup/classes as the Blade header, same
 * polling behavior, reimplemented in React instead of jQuery.
 */
export default function AppHeader({
  siteTitle = "e-Clothing",
  notifContext,
}: {
  siteTitle?: string;
  notifContext: "member" | "admin" | null;
}) {
  const { toggle } = useSidebar();
  const indexUrl = notifContext === "admin" ? "/api/admin/notifications" : "/api/notifications";
  const readUrl = notifContext === "admin" ? "/api/admin/notifications/read" : "/api/notifications/read";

  const [open, setOpen] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [notifications, setNotifications] = useState<NotificationItem[] | null>(null);
  const wrapRef = useRef<HTMLDivElement>(null);

  const fetchNotifications = useCallback(
    async (markAsRead: boolean) => {
      try {
        const res = await fetch(indexUrl);
        const data = await res.json();
        setUnreadCount(data.unread_count ?? 0);
        if (markAsRead) {
          setNotifications(data.notifications ?? []);
          if ((data.unread_count ?? 0) > 0) {
            const readRes = await apiPost(readUrl);
            const readData = await readRes.json();
            setUnreadCount(readData.unread_count ?? 0);
          }
        }
      } catch {
        if (markAsRead) setNotifications([]);
      }
    },
    [indexUrl, readUrl],
  );

  useEffect(() => {
    if (!notifContext) return;
    // Standard fetch-on-mount + poll: fetchNotifications only sets state
    // after its own await resolves, never synchronously in this effect
    // body -- the rule's static analysis can't see that through the
    // useCallback indirection.
    // eslint-disable-next-line react-hooks/set-state-in-effect
    void fetchNotifications(false);
    const interval = setInterval(() => void fetchNotifications(open), 30000);
    return () => clearInterval(interval);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [notifContext]);

  useEffect(() => {
    function onDocClick(e: MouseEvent) {
      if (open && wrapRef.current && !wrapRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    }
    document.addEventListener("click", onDocClick);
    return () => document.removeEventListener("click", onDocClick);
  }, [open]);

  function handleBellClick(e: React.MouseEvent) {
    e.stopPropagation();
    const next = !open;
    setOpen(next);
    if (next) fetchNotifications(true);
  }

  return (
    <header className="header">
      <div className="topbar">
        <div className="topbar-left">
          <button
            type="button"
            className="navbar-togglee"
            aria-label="Menu"
            aria-controls="userSidebar"
            aria-expanded={false}
            onClick={toggle}
          >
            <span className="icon-bar"></span>
            <span className="icon-bar"></span>
            <span className="icon-bar"></span>
          </button>
          <div className="topbar-title">{siteTitle}</div>
        </div>

        <div className="topbar-right">
          <button type="button" className="btn btn-default topbar-action" aria-label="Search">
            <i className="fa fa-search" aria-hidden="true"></i>
          </button>

          {notifContext && (
            <div className="notif-wrap" id="notifWrap" ref={wrapRef}>
              <button
                type="button"
                className="btn btn-default topbar-action"
                aria-label="Notifications"
                aria-haspopup="true"
                aria-expanded={open}
                onClick={handleBellClick}
              >
                <i className="fa fa-bell-o" aria-hidden="true"></i>
                {unreadCount > 0 && (
                  <span className="notif-badge">{unreadCount > 99 ? "99+" : unreadCount}</span>
                )}
              </button>
              {open && (
                <div className="notif-dropdown" style={{ display: "block" }}>
                  <div className="notif-dropdown-header">Pemberitahuan</div>
                  <div className="notif-dropdown-list">
                    {notifications === null ? (
                      <div className="notif-empty">Memuatkan...</div>
                    ) : notifications.length === 0 ? (
                      <div className="notif-empty">Tiada pemberitahuan.</div>
                    ) : (
                      notifications.map((n) => (
                        <div key={n.id} className={`notif-item${n.read ? "" : " notif-unread"}`}>
                          <div className="notif-item-title">{n.title}</div>
                          <div className="notif-item-body">{n.body}</div>
                          <div className="notif-item-time">{n.created_at}</div>
                        </div>
                      ))
                    )}
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
