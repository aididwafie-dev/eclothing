import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type { AdminBasesResponse } from "@/lib/types";
import BasesPageClient from "./BasesPageClient";

export default async function AdminBasesPage() {
  let data: AdminBasesResponse;
  try {
    data = await laravelFetchAdminAuthed<AdminBasesResponse>("/admin/bases");
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/admin/login");
    }
    throw err;
  }

  return <BasesPageClient initialBases={data.bases} />;
}
