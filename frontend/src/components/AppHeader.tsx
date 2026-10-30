"use client";

import Link from "next/link";
import { useCallback, useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { apiPost, apiUrl } from "@/lib/clientApi";
import { useSidebar } from "./AppShell";

type NotificationItem = {
  id: number;
  type: string;
  title: string;
  body: string;
  order_id: number | null;
  created_at: string;
  read: boolean;
};

/** Icon and colours for each type from OrderNotificationService / AdminNotificationService. */
const TYPE_ICONS: Record<string, { icon: string; color: string; bg: string }> = {
  order_approved: { icon: "fa-check-circle", color: "#1a7f45", bg: "rgba(26, 127, 69, 0.12)" },
  admin_order_approved: { icon: "fa-check-circle", color: "#1a7f45", bg: "rgba(26, 127, 69, 0.12)" },
  order_completed: { icon: "fa-check", color: "#1a7f45", bg: "rgba(26, 127, 69, 0.12)" },
  order_rejected: { icon: "fa-times-circle", color: "#c0392b", bg: "rgba(192, 57, 43, 0.12)" },
  order_collection_timeout: { icon: "fa-clock-o", color: "#c0392b", bg: "rgba(192, 57, 43, 0.12)" },
  order_processing: { icon: "fa-cog", color: "#0f5bb5", bg: "rgba(51, 145, 255, 0.12)" },
  order_collection_date_updated: { icon: "fa-calendar", color: "#0f5bb5", bg: "rgba(51, 145, 255, 0.12)" },
  order_remarks_updated: { icon: "fa-comment-o", color: "#0f5bb5", bg: "rgba(51, 145, 255, 0.12)" },
  admin_new_order: { icon: "fa-shopping-cart", color: "#6b5200", bg: "rgba(249, 209, 22, 0.2)" },
  admin_order_edited: { icon: "fa-pencil", color: "#6b5200", bg: "rgba(249, 209, 22, 0.2)" },
  announcement: { icon: "fa-bullhorn", color: "#6b5200", bg: "rgba(249, 209, 22, 0.2)" },
};
const DEFAULT_ICON = { icon: "fa-bell-o", color: "#0b1f3a", bg: "rgba(0, 26, 56, 0.06)" };

/** "Y-m-d H:i:s" (server local time) -> "5 minit", "3 jam", "1 hari", like the reference's "1 day". */
function timeAgo(value: string) {
  const then = new Date(value.replace(" ", "T"));
  if (Number.isNaN(then.getTime())) return value;
  const minutes = Math.floor((Date.now() - then.getTime()) / 60000);
  if (minutes < 1) return "Baru sahaja";
  if (minutes < 60) return `${minutes} minit`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} jam`;
  const days = Math.floor(hours / 24);
  if (days < 30) return `${days} hari`;
  return then.toLocaleDateString("ms-MY", { day: "numeric", month: "short", year: "numeric" });
}

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
  const [panelPos, setPanelPos] = useState<{ top: number; right: number } | null>(null);
  const wrapRef = useRef<HTMLDivElement>(null);
  const bellRef = useRef<HTMLButtonElement>(null);
  const panelRef = useRef<HTMLDivElement>(null);

  const fetchNotifications = useCallback(
    async (markAsRead: boolean) => {
      try {
        const res = await fetch(apiUrl(indexUrl));
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
    if (!open) return;
    function onDocClick(e: MouseEvent) {
      const target = e.target as Node;
      if (!wrapRef.current?.contains(target) && !panelRef.current?.contains(target)) {
        setOpen(false);
      }
    }
    function onKeyDown(e: KeyboardEvent) {
      if (e.key === "Escape") setOpen(false);
    }
    // The panel is fixed to the viewport, so keep it pinned under the
    // bell when the window is resized or the page scrolls.
    function reposition() {
      setPanelPos(bellPosition());
    }
    document.addEventListener("click", onDocClick);
    document.addEventListener("keydown", onKeyDown);
    window.addEventListener("resize", reposition);
    window.addEventListener("scroll", reposition, true);
    return () => {
      document.removeEventListener("click", onDocClick);
      document.removeEventListener("keydown", onKeyDown);
      window.removeEventListener("resize", reposition);
      window.removeEventListener("scroll", reposition, true);
    };
  }, [open]);

  /** Where the floating panel goes: just below the bell, right-aligned to it, kept 16px inside the viewport. */
  function bellPosition() {
    const rect = bellRef.current?.getBoundingClientRect();
    if (!rect) return null;
    const panelWidth = Math.min(520, window.innerWidth - 32);
    const right = Math.min(Math.max(16, window.innerWidth - rect.right), window.innerWidth - panelWidth - 16);
    return { top: rect.bottom + 12, right };
  }

  /** Order notifications open the order; there is no admin "all notifications" page, so admins get no footer. */
  function itemHref(n: NotificationItem) {
    if (n.order_id === null) return null;
    return notifContext === "admin" ? `/admin/orders/${n.order_id}` : "/orders";
  }

  function handleBellClick(e: React.MouseEvent) {
    e.stopPropagation();
    const next = !open;
    if (next) setPanelPos(bellPosition());
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
                ref={bellRef}
              >
                <i className="fa fa-bell-o" aria-hidden="true"></i>
                {unreadCount > 0 && (
                  <span className="notif-badge">{unreadCount > 99 ? "99+" : unreadCount}</span>
                )}
              </button>
              {open &&
                panelPos &&
                createPortal(
                // Portalled to <body> with fixed positioning so no header,
                // grid or overflow rule can clip or cover it.
                <div
                  ref={panelRef}
                  className="notif-panel"
                  role="dialog"
                  aria-label="Pemberitahuan"
                  style={{ top: panelPos.top, right: panelPos.right }}
                >
                  <div className="notif-panel-header">
                    <h2 className="notif-panel-title">Pemberitahuan</h2>
                    <button
                      type="button"
                      className="notif-panel-close"
                      aria-label="Tutup"
                      onClick={() => setOpen(false)}
                    >
                      <i className="fa fa-times" aria-hidden="true"></i>
                    </button>
                  </div>
                  <div className="notif-panel-list">
                    {notifications === null ? (
                      <div className="notif-panel-empty">Memuatkan...</div>
                    ) : notifications.length === 0 ? (
                      <div className="notif-panel-empty">Tiada pemberitahuan.</div>
                    ) : (
                      notifications.map((n) => {
                        const icon = TYPE_ICONS[n.type] ?? DEFAULT_ICON;
                        const href = itemHref(n);
                        const content = (
                          <>
                            <span className="notif-panel-icon" style={{ color: icon.color, background: icon.bg }}>
                              <i className={`fa ${icon.icon}`} aria-hidden="true"></i>
                            </span>
                            <span className="notif-panel-text">
                              <span className="notif-panel-item-title" style={{ display: "block" }}>{n.title}</span>
                              <span className="notif-panel-item-body">{n.body}</span>
                            </span>
                            <span className="notif-panel-time" title={n.created_at}>
                              {timeAgo(n.created_at)}
                            </span>
                          </>
                        );
                        const className = `notif-panel-item${n.read ? "" : " is-unread"}`;
                        return href ? (
                          <Link key={n.id} href={href} className={className} onClick={() => setOpen(false)}>
                            {content}
                          </Link>
                        ) : (
                          <div key={n.id} className={className}>
                            {content}
                          </div>
                        );
                      })
                    )}
                  </div>
                  {notifContext === "member" && (
                    <Link href="/notifications" className="notif-panel-footer" onClick={() => setOpen(false)}>
                      Lihat Semua
                    </Link>
                  )}
                </div>,
                document.body,
              )}
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
