import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type { AdminBaseDetailResponse } from "@/lib/types";
import BaseEditForm from "./BaseEditForm";

export default async function AdminBaseEditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  let data: AdminBaseDetailResponse;
  try {
    data = await laravelFetchAdminAuthed<AdminBaseDetailResponse>(`/admin/bases/${id}`);
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/admin/login");
    }
    throw err;
  }

  return <BaseEditForm data={data} />;
}
