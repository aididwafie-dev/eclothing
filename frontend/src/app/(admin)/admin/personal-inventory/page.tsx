import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type { PersonalInventoryResponse } from "@/lib/types";
import PersonalInventoryFilters from "./PersonalInventoryFilters";

export default async function AdminPersonalInventoryPage({
  searchParams,
}: {
  searchParams: Promise<{ uniform?: string; month?: string; year?: string; search?: string; page?: string }>;
}) {
  const params = await searchParams;

  const qs = new URLSearchParams();
  if (params.uniform) qs.set("uniform", params.uniform);
  if (params.month) qs.set("month", params.month);
  if (params.year) qs.set("year", params.year);
  if (params.search) qs.set("search", params.search);
  if (params.page) qs.set("page", params.page);

  let data: PersonalInventoryResponse;
  try {
    data = await laravelFetchAdminAuthed<PersonalInventoryResponse>(`/admin/personal-inventory?${qs}`);
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/admin/login");
    }
    throw err;
  }

  return (
    <div className="rounded-lg border border-black/10 bg-white p-6">
      <h1 className="text-xl font-semibold">Personal Inventory</h1>
      <PersonalInventoryFilters data={data} currentSearch={params.search ?? ""} />
    </div>
  );
}
