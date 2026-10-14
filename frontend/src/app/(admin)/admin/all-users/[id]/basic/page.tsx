import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type { AdminUserDetailResponse } from "@/lib/types";
import BasicDetailsForm from "./BasicDetailsForm";

export default async function AdminUserBasicDetailsPage({
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

  return (
    <>
      <div className="title">
        <i className="fa fa-address-card" aria-hidden="true"></i> Users Basic Details
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <div className="title">
            <i className="fa fa-pencil" aria-hidden="true"></i> Edit Basic Details:
          </div>
          <hr />
          <BasicDetailsForm userId={id} user={data.user} />
        </div>
      </div>
    </>
  );
}
