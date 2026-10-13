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
    <div className="rounded-lg border border-black/10 bg-white p-6">
      <h1 className="text-xl font-semibold">Notifications</h1>
      <NotificationsList initialData={data} />
    </div>
  );
}
