import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";
import type { NotificationsResponse } from "@/lib/types";
import NotificationsList from "./NotificationsList";

export default async function NotificationsPage() {
  let data: NotificationsResponse;
  try {
    data = await laravelFetchAuthed<NotificationsResponse>("/notifications?limit=50");
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/login");
    }
    throw err;
  }

  return (
    <>
      <div className="title">
        <i className="fa fa-bell-o" aria-hidden="true"></i> Notifications
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <NotificationsList initialData={data} />
        </div>
      </div>
    </>
  );
}
