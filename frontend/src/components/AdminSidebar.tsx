"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { useSidebar } from "./AppShell";
import { apiPost, apiUrl } from "@/lib/clientApi";

const SUPERADMIN_LINKS = [
  { href: "/admin/new-admin", label: "Add New Admin", icon: "fa-user-plus" },
  { href: "/admin/all-admins", label: "All Admins", icon: "fa-user-circle-o" },
  { href: "/admin/all-users", label: "All Users", icon: "fa-users" },
];

const SHARED_LINKS = [
  { href: "/admin/orders", label: "Uniform Orders", icon: "fa-clipboard" },
  { href: "/admin/personal-inventory", label: "Personal Inventory", icon: "fa-archive" },
];

const SUPERADMIN_LINKS_2 = [
  { href: "/admin/uniforms", label: "Uniforms", icon: "fa-shirtsinbulk" },
  { href: "/admin/units", label: "Units", icon: "fa-arrows-h" },
  { href: "/admin/tred", label: "Tred", icon: "fa-shirtsinbulk" },
  { href: "/admin/bases", label: "Bases", icon: "fa-building" },
  { href: "/admin/orders-report", label: "Order Reports", icon: "fa-print" },
  { href: "/admin/users-report", label: "User Reports", icon: "fa-print" },
  { href: "/admin/announcements", label: "Announcements", icon: "fa-envelope-o" },
];

/** The link itself or anything under it -- not /admin/orders-report for /admin/orders. */
function isActive(pathname: string, href: string) {
  return pathname === href || pathname.startsWith(`${href}/`);
}

function NavLink({
  href,
  label,
  icon,
  active,
}: {
  href: string;
  label: string;
  icon: string;
  active: boolean;
}) {
  return (
    <Link href={href}>
      <button type="button" className={`btn btn-pnl btn-block${active ? " active" : ""}`}>
        <i className={`fa ${icon}`} aria-hidden="true"></i> {label}
      </button>
    </Link>
  );
}

export default function AdminSidebar() {
  const pathname = usePathname();
  const router = useRouter();
  const { close } = useSidebar();
  const [role, setRole] = useState<"superadmin" | "orders" | null>(null);

  useEffect(() => {
    void fetch(apiUrl("/api/admin/auth/me"))
      .then((res) => res.json())
      .then((data) => setRole(data.admin?.role ?? "superadmin"))
      .catch(() => setRole("superadmin"));
  }, []);

  const isOrdersOnly = role === "orders";

  async function handleLogout() {
    await apiPost("/api/admin/auth/logout");
    router.push("/admin/login");
    router.refresh();
  }

  return (
    <>
      <div className="sidebar-brand">
        <img src="/front_end/images/logo.png" className="sidebar-logo" alt="logo" />
        <div className="sidebar-brand-title">e-Clothing</div>
      </div>

      <button type="button" onClick={close} className="btn btn-link btn-pnl btn-block sidebar-close">
        <i className="fa fa-times" aria-hidden="true"></i> Close
      </button>

      {!isOrdersOnly &&
        SUPERADMIN_LINKS.map((l) => <NavLink key={l.href} {...l} active={isActive(pathname, l.href)} />)}
      {SHARED_LINKS.map((l) => <NavLink key={l.href} {...l} active={isActive(pathname, l.href)} />)}
      {!isOrdersOnly &&
        SUPERADMIN_LINKS_2.map((l) => <NavLink key={l.href} {...l} active={isActive(pathname, l.href)} />)}

      <NavLink
        href="/admin/change-password"
        label="Change Password"
        icon="fa-lock"
        active={isActive(pathname, "/admin/change-password")}
      />

      {!isOrdersOnly && (
        <NavLink
          href="/admin/system-settings"
          label="System Settings"
          icon="fa-cog"
          active={isActive(pathname, "/admin/system-settings")}
        />
      )}

      <button type="button" onClick={handleLogout} className="btn btn-pnl btn-block">
        <i className="fa fa-sign-out" aria-hidden="true"></i> Logout
      </button>
    </>
  );
}
