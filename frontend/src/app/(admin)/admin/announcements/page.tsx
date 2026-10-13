import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type { AudienceCountsResponse } from "@/lib/types";
import AnnouncementsForm from "./AnnouncementsForm";

export default async function AdminAnnouncementsPage() {
  let data: AudienceCountsResponse;
  try {
    data = await laravelFetchAdminAuthed<AudienceCountsResponse>("/admin/announcements/audience-counts");
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/admin/login");
    }
    throw err;
  }

  return <AnnouncementsForm audienceCounts={data.audienceCounts} />;
}
