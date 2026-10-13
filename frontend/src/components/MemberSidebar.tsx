"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useSidebar } from "./AppShell";
import { apiPost } from "@/lib/clientApi";

const LINKS = [
  { href: "/personal-details", label: "Personal Details", icon: "fa-address-card-o" },
  { href: "/cart", label: "Order Uniform", icon: "fa-shirtsinbulk" },
  { href: "/orders", label: "Uniform Ordered", icon: "fa-shopping-bag" },
  { href: "/change-email", label: "Change Email Id", icon: "fa-envelope-square" },
  { href: "/change-password", label: "Change Password", icon: "fa-lock" },
];

export default function MemberSidebar() {
  const pathname = usePathname();
  const router = useRouter();
  const { close } = useSidebar();

  async function handleLogout() {
    if (!confirm("Are you sure you would like to logout? All unsaved changes will be lost.")) return;
    await apiPost("/api/auth/logout");
    router.push("/login");
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

      {LINKS.map((link) => (
        <Link
          key={link.href}
          href={link.href}
          className={`btn btn-link btn-pnl btn-block${pathname === link.href ? " active" : ""}`}
        >
          <i className={`fa ${link.icon}`} aria-hidden="true"></i> {link.label}
        </Link>
      ))}

      <button type="button" onClick={handleLogout} className="btn btn-link btn-pnl btn-block">
        <i className="fa fa-sign-out" aria-hidden="true"></i> Logout
      </button>
    </>
  );
}
