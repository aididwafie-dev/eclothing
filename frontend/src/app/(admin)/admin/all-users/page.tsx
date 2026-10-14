import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type { AdminUsersResponse } from "@/lib/types";
import UsersFilters from "./UsersFilters";

export default async function AdminAllUsersPage({
  searchParams,
}: {
  searchParams: Promise<{ filter?: string; search?: string; page?: string }>;
}) {
  const params = await searchParams;

  const qs = new URLSearchParams();
  if (params.filter) qs.set("filter", params.filter);
  if (params.search) qs.set("search", params.search);
  if (params.page) qs.set("page", params.page);

  let data: AdminUsersResponse;
  try {
    data = await laravelFetchAdminAuthed<AdminUsersResponse>(`/admin/users?${qs}`);
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/admin/login");
    }
    throw err;
  }

  return (
    <>
      <div className="title">
        <i className="fa fa-users" aria-hidden="true"></i> All Users
      </div>
      <hr />
      <UsersFilters data={data} currentSearch={params.search ?? ""} />
    </>
  );
}
