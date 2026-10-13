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

type Tab = "site" | "uniform" | "scale" | "notifications";

const TABS: { value: Tab; label: string }[] = [
  { value: "site", label: "System" },
  { value: "uniform", label: "Uniforms" },
  { value: "scale", label: "Entitlement Scale" },
  { value: "notifications", label: "Notifications" },
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
    <div className="rounded-lg border border-black/10 bg-white">
      <div className="flex border-b border-black/10">
        {TABS.map((t) => (
          <button
            key={t.value}
            type="button"
            onClick={() => switchTab(t.value)}
            className={`px-4 py-3 text-sm font-medium ${
              tab === t.value ? "border-b-2 border-slate-900" : "text-black/50"
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>
      <div className="p-6">
        {tab === "site" && <SiteTab initialSiteTitle={site.siteTitle} />}
        {tab === "uniform" && <UniformTab initialGroups={uniforms.groups} />}
        {tab === "scale" && <ScaleTab initialData={scale} />}
        {tab === "notifications" && <NotificationsTab initialData={notifications} />}
      </div>
    </div>
  );
}
