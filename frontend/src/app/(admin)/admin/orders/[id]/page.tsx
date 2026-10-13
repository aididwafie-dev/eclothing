import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type { AdminOrderDetailResponse } from "@/lib/types";
import OrderDetailForm from "./OrderDetailForm";

export default async function AdminOrderDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  let data: AdminOrderDetailResponse;
  try {
    data = await laravelFetchAdminAuthed<AdminOrderDetailResponse>(`/admin/orders/${id}`);
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/admin/login");
    }
    throw err;
  }

  return <OrderDetailForm data={data} />;
}
