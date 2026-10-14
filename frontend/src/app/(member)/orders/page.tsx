import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";
import type { OrdersResponse } from "@/lib/types";
import OrdersPageClient from "./OrdersPageClient";

export default async function OrdersPage() {
  let data: OrdersResponse;
  try {
    data = await laravelFetchAuthed<OrdersResponse>("/orders");
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/login");
    }
    throw err;
  }

  return <OrdersPageClient orders={data.orders} />;
}
