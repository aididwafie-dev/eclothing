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

  return (
    <>
      <div className="title">
        <i className="fa fa-address-card" aria-hidden="true"></i> Users Personal Details
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <div className="title">
            <i className="fa fa-pencil" aria-hidden="true"></i> Edit Users Personal Details:
          </div>
          <hr />
          {data.personalDetails ? (
            <PersonalDetailsEditForm userId={id} data={data} />
          ) : (
            "This User have not filled his personal details yet."
          )}
        </div>
      </div>
    </>
  );
}
