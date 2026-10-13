import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type { AdminOrdersResponse } from "@/lib/types";
import OrdersFilters from "./OrdersFilters";

export default async function AdminOrdersPage({
  searchParams,
}: {
  searchParams: Promise<{ status?: string; unit?: string; search?: string; page?: string }>;
}) {
  const params = await searchParams;

  const qs = new URLSearchParams();
  if (params.status) qs.set("status", params.status);
  if (params.unit) qs.set("unit", params.unit);
  if (params.search) qs.set("search", params.search);
  if (params.page) qs.set("page", params.page);

  let data: AdminOrdersResponse;
  try {
    data = await laravelFetchAdminAuthed<AdminOrdersResponse>(`/admin/orders?${qs}`);
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/admin/login");
    }
    throw err;
  }

  return (
    <div className="rounded-lg border border-black/10 bg-white p-6">
      <h1 className="text-xl font-semibold">Uniform Orders</h1>
      <OrdersFilters data={data} currentSearch={params.search ?? ""} />
    </div>
  );
}
