import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type { PersonalInventoryDetailResponse } from "@/lib/types";
import InventoryDetailFilters from "./InventoryDetailFilters";

export default async function AdminPersonalInventoryDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ month?: string; year?: string }>;
}) {
  const { id } = await params;
  const { month, year } = await searchParams;

  const qs = new URLSearchParams();
  if (month) qs.set("month", month);
  if (year) qs.set("year", year);

  let data: PersonalInventoryDetailResponse;
  try {
    data = await laravelFetchAdminAuthed<PersonalInventoryDetailResponse>(
      `/admin/personal-inventory/${id}?${qs}`,
    );
  } catch (err) {
    if (err instanceof LaravelApiError && err.status === 401) {
      redirect("/admin/login");
    }
    throw err;
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="rounded-lg border border-black/10 bg-white p-6">
        <h1 className="text-xl font-semibold">{data.member.name ?? data.member.sId}</h1>
        <dl className="mt-3 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
          <div>
            <dt className="text-black/50">Service ID</dt>
            <dd>{data.member.sId}</dd>
          </div>
          <div>
            <dt className="text-black/50">Rank</dt>
            <dd>{data.member.rankName ?? "-"}</dd>
          </div>
          <div>
            <dt className="text-black/50">Unit</dt>
            <dd>{data.member.unitName ?? "N/A"}</dd>
          </div>
          <div>
            <dt className="text-black/50">Totals</dt>
            <dd>
              {data.orderTotal} order(s), {data.itemTotal} item(s)
            </dd>
          </div>
        </dl>
        <InventoryDetailFilters filters={data.filters} />
      </div>

      {data.groups.length === 0 ? (
        <div className="rounded-lg border border-black/10 bg-white p-6 text-sm text-black/50">
          No uniform ordered by this member for this period. Counted as 0.
        </div>
      ) : (
        data.groups.map((group) => (
          <div key={group.uniformId} className="rounded-lg border border-black/10 bg-white p-6">
            <h2 className="font-semibold">
              {group.label} &mdash; {group.itemCount} item(s) across {group.orderCount} order(s)
            </h2>
            <div className="mt-3 flex flex-col gap-4">
              {group.orders.map((order) => (
                <div key={order.id} className="rounded-md border border-black/10 p-4">
                  <div className="flex items-center justify-between">
                    <span className="font-medium">Order #{order.id}</span>
                    <span className="rounded bg-black/5 px-2 py-0.5 text-sm">{order.statusLabel}</span>
                  </div>
                  <div className="mt-1 text-sm text-black/50">
                    {order.createdAt}
                    {order.collectionDate ? ` · Collection: ${order.collectionDate}` : ""}
                  </div>
                  {order.remarks && <div className="mt-1 text-sm">{order.remarks}</div>}
                  <table className="mt-2 w-full text-left text-sm">
                    <thead>
                      <tr className="text-black/50">
                        <th className="py-1">Item</th>
                        <th className="py-1">Size</th>
                        <th className="py-1">Qty</th>
                      </tr>
                    </thead>
                    <tbody>
                      {order.items.map((item, i) => (
                        <tr key={i}>
                          <td className="py-1">{item.clothes}</td>
                          <td className="py-1">{item.size}</td>
                          <td className="py-1">{item.quantity}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ))}
            </div>
          </div>
        ))
      )}
    </div>
  );
}
