"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import type {
  NotificationSettingsResponse,
  ScaleResponse,
  SiteSettingsResponse,
  UniformsSettingsResponse,
} from "@/lib/types";
import NotificationsTab from "./NotificationsTab";
import ScaleTab from "./ScaleTab";
import SiteTab from "./SiteTab";
import UniformTab from "./UniformTab";

type Tab = "system" | "uniform" | "scale" | "notifications";

const TABS: { value: Tab; label: string }[] = [
  { value: "system", label: "Tetapan Sistem" },
  { value: "uniform", label: "Tetapan Pakaian" },
  { value: "scale", label: "Skala Pakaian & Kelayakan" },
  { value: "notifications", label: "Pemberitahuan" },
];

export default function SystemSettingsTabs({
  initialTab,
  site,
  uniforms,
  scale,
  notifications,
}: {
  initialTab: Tab;
  site: SiteSettingsResponse;
  uniforms: UniformsSettingsResponse;
  scale: ScaleResponse;
  notifications: NotificationSettingsResponse;
}) {
  const router = useRouter();
  const [tab, setTab] = useState<Tab>(initialTab);

  function switchTab(next: Tab) {
    setTab(next);
    router.replace(`/admin/system-settings?tab=${next}`, { scroll: false });
  }

  return (
    <>
      <div className="title">
        <i className="fa fa-cog" aria-hidden="true"></i> Tetapan Sistem
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <ul className="nav nav-tabs" role="tablist">
            {TABS.map((t) => (
              <li key={t.value} role="presentation" className={tab === t.value ? "active" : ""}>
                <a onClick={() => switchTab(t.value)} role="tab" style={{ cursor: "pointer" }}>
                  {t.label}
                </a>
              </li>
            ))}
          </ul>

          <div className="tab-content" style={{ paddingTop: 16 }}>
            <div className={`tab-pane ${tab === "system" ? "active" : ""}`}>
              {tab === "system" && <SiteTab initialSiteTitle={site.siteTitle} initialLogoUrl={site.logoUrl} />}
            </div>
            <div className={`tab-pane ${tab === "uniform" ? "active" : ""}`}>
              {tab === "uniform" && <UniformTab initialGroups={uniforms.groups} />}
            </div>
            <div className={`tab-pane ${tab === "scale" ? "active" : ""}`}>
              {tab === "scale" && <ScaleTab initialData={scale} />}
            </div>
            <div className={`tab-pane ${tab === "notifications" ? "active" : ""}`}>
              {tab === "notifications" && <NotificationsTab initialData={notifications} />}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
