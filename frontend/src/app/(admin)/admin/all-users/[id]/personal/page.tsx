import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type { AdminUserDetailResponse } from "@/lib/types";
import PersonalDetailsEditForm from "./PersonalDetailsEditForm";

export default async function AdminUserPersonalDetailsPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  let data: AdminUserDetailResponse;
  try {
    data = await laravelFetchAdminAuthed<AdminUserDetailResponse>(`/admin/users/${id}`);
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/admin/login");
    }
    throw err;
  }

  if (!data.personalDetails) {
    return (
      <div className="rounded-lg border border-black/10 bg-white p-6">
        <h1 className="text-xl font-semibold">Edit Personal Details</h1>
        <p className="mt-4 text-sm text-black/50">This user has not filled in their personal details yet.</p>
      </div>
    );
  }

  return <PersonalDetailsEditForm userId={id} data={data} />;
}
