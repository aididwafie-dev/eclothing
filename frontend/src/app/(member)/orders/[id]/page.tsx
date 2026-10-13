import { notFound, redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";
import type { OrdersResponse } from "@/lib/types";

export default async function OrderDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  let data: OrdersResponse;
  try {
    data = await laravelFetchAuthed<OrdersResponse>("/orders");
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/login");
    }
    throw err;
  }

  const order = data.orders.find((o) => o.id === id);
  if (!order) {
    notFound();
  }

  return (
    <div className="rounded-lg border border-black/10 bg-white p-6">
      <h1 className="text-xl font-semibold">Order #{order.id}</h1>
      <dl className="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
        <div>
          <dt className="text-black/50">Uniform</dt>
          <dd>{order.uniformName ?? order.uniformType}</dd>
        </div>
        <div>
          <dt className="text-black/50">Status</dt>
          <dd>{order.statusLabel}</dd>
        </div>
        <div>
          <dt className="text-black/50">Collection Date</dt>
          <dd>{order.collectionDate ?? "-"}</dd>
        </div>
        <div>
          <dt className="text-black/50">Updated</dt>
          <dd>{order.updatedAt ?? "-"}</dd>
        </div>
        {order.remarks && (
          <div className="col-span-full">
            <dt className="text-black/50">Remarks</dt>
            <dd>{order.remarks}</dd>
          </div>
        )}
      </dl>

      <h2 className="mt-6 font-semibold">Items</h2>
      <table className="mt-3 w-full text-left text-sm">
        <thead>
          <tr className="border-b border-black/10 text-black/50">
            <th className="py-2">Item</th>
            <th className="py-2">Size</th>
          </tr>
        </thead>
        <tbody>
          {order.items.map((item, i) => (
            <tr key={i} className="border-b border-black/5">
              <td className="py-2">{item.clothes}</td>
              <td className="py-2">{item.size}</td>
            </tr>
          ))}
        </tbody>
      </table>

      {order.editable && (
        <p className="mt-4 text-sm text-black/50">
          This order is still Pending and can be edited from the Order Uniform page.
        </p>
      )}
    </div>
  );
}
