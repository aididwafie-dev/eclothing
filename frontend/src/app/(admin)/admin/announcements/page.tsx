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

  return (
    <>
      <div className="title">
        <i className="fa fa-envelope-o" aria-hidden="true"></i> Send announcement to user(s)
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <div className="title">
            <i className="fa fa-envelope" aria-hidden="true"></i> Send announcement:
          </div>
          <hr />
          <AnnouncementsForm audienceCounts={data.audienceCounts} />
        </div>
      </div>
    </>
  );
}
