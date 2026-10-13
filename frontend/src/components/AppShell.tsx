"use client";

import { createContext, useContext, useEffect, useState } from "react";

/**
 * The app-shell / app-grid / bd-sidebar / rightPanel structure from
 * static-layout/header.blade.php + sidebar.blade.php, reimplemented with
 * React state instead of the original jQuery (open/close on the
 * hamburger button, Escape, resize, backdrop click, closing on nav
 * click on mobile) -- same classes, so style.css styles it identically.
 */
const SidebarContext = createContext<{ open: boolean; toggle: () => void; close: () => void } | null>(null);

export function useSidebar() {
  const ctx = useContext(SidebarContext);
  if (!ctx) throw new Error("useSidebar must be used within AppShell");
  return ctx;
}

export default function AppShell({
  header,
  sidebar,
  children,
}: {
  header: React.ReactNode;
  sidebar: React.ReactNode;
  children: React.ReactNode;
}) {
  const [open, setOpen] = useState(false);

  useEffect(() => {
    document.body.classList.toggle("sidebar-open", open);
    return () => document.body.classList.remove("sidebar-open");
  }, [open]);

  useEffect(() => {
    function onKeyDown(e: KeyboardEvent) {
      if (e.key === "Escape") setOpen(false);
    }
    function onResize() {
      if (window.innerWidth > 820) setOpen(false);
    }
    window.addEventListener("keydown", onKeyDown);
    window.addEventListener("resize", onResize);
    return () => {
      window.removeEventListener("keydown", onKeyDown);
      window.removeEventListener("resize", onResize);
    };
  }, []);

  const ctx = { open, toggle: () => setOpen((v) => !v), close: () => setOpen(false) };

  return (
    <SidebarContext.Provider value={ctx}>
      <div className="app-shell">
        <div className="app-grid">
          {header}
          <div className="sidebar-backdrop" onClick={ctx.close} />
          <div className={`bd-sidebar pull-left${open ? " show" : ""}`}>
            <nav
              className="collapse bd-links"
              onClick={() => window.innerWidth <= 820 && ctx.close()}
            >
              {sidebar}
            </nav>
          </div>
          <div className="rightPanel app-main">{children}</div>
        </div>
      </div>
    </SidebarContext.Provider>
  );
}
