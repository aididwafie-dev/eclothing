import Link from "next/link";
import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";
import type { OrdersResponse } from "@/lib/types";

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

  return (
    <div className="rounded-lg border border-black/10 bg-white p-6">
      <h1 className="text-xl font-semibold">My Orders</h1>

      {data.orders.length === 0 ? (
        <p className="mt-4 text-sm text-black/50">You have not placed any orders yet.</p>
      ) : (
        <table className="mt-4 w-full text-left text-sm">
          <thead>
            <tr className="border-b border-black/10 text-black/50">
              <th className="py-2">Order</th>
              <th className="py-2">Uniform</th>
              <th className="py-2">Items</th>
              <th className="py-2">Status</th>
              <th className="py-2">Updated</th>
              <th className="py-2"></th>
            </tr>
          </thead>
          <tbody>
            {data.orders.map((order) => (
              <tr key={order.id} className="border-b border-black/5">
                <td className="py-2">#{order.id}</td>
                <td className="py-2">{order.uniformName ?? order.uniformType}</td>
                <td className="py-2">{order.itemCount}</td>
                <td className="py-2">
                  <span className="rounded bg-black/5 px-2 py-0.5">{order.statusLabel}</span>
                </td>
                <td className="py-2">{order.updatedAt ?? "-"}</td>
                <td className="py-2">
                  <Link href={`/orders/${order.id}`} className="text-blue-600 hover:underline">
                    View
                  </Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
