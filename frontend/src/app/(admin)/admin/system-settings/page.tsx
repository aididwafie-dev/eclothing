import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type {
  NotificationSettingsResponse,
  ScaleResponse,
  SiteSettingsResponse,
  UniformsSettingsResponse,
} from "@/lib/types";
import SystemSettingsTabs from "./SystemSettingsTabs";

export default async function AdminSystemSettingsPage({
  searchParams,
}: {
  searchParams: Promise<{ tab?: string }>;
}) {
  const { tab } = await searchParams;

  let site: SiteSettingsResponse;
  let uniforms: UniformsSettingsResponse;
  let scale: ScaleResponse;
  let notifications: NotificationSettingsResponse;
  try {
    [site, uniforms, scale, notifications] = await Promise.all([
      laravelFetchAdminAuthed<SiteSettingsResponse>("/admin/system-settings/site"),
      laravelFetchAdminAuthed<UniformsSettingsResponse>("/admin/system-settings/uniforms"),
      laravelFetchAdminAuthed<ScaleResponse>("/admin/system-settings/scale"),
      laravelFetchAdminAuthed<NotificationSettingsResponse>("/admin/system-settings/notifications"),
    ]);
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/admin/login");
    }
    throw err;
  }

  return (
    <SystemSettingsTabs
      initialTab={tab === "uniform" || tab === "scale" || tab === "notifications" ? tab : "site"}
      site={site}
      uniforms={uniforms}
      scale={scale}
      notifications={notifications}
    />
  );
}
