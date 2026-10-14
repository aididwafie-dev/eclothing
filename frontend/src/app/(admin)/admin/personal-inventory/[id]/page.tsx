import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAdminAuthed } from "@/lib/laravel";
import type { PersonalInventoryDetailResponse } from "@/lib/types";
import InventoryDetailFilters from "./InventoryDetailFilters";

const MONTHS = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December",
];

function formatDate(value: string | null): string {
  if (!value) return "-";
  const d = new Date(value.replace(" ", "T"));
  if (isNaN(d.getTime())) return value;
  const day = String(d.getDate()).padStart(2, "0");
  const month = d.toLocaleDateString("en-GB", { month: "short" });
  return `${day} ${month} ${d.getFullYear()}`;
}

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

  const periodLabel =
    data.filters.month === "all" && data.filters.year === "all"
      ? "all dates"
      : [
          data.filters.month === "all" ? "" : MONTHS[Number(data.filters.month) - 1],
          data.filters.year === "all" ? "every year" : data.filters.year,
        ]
          .filter(Boolean)
          .join(" ");

  return (
    <>
      <div className="title">
        <i className="fa fa-archive" aria-hidden="true"></i> Personal Inventory &mdash; {data.member.sId}
      </div>
      <hr />
      <div className="containerMain">
        <div className="content full">
          <div className="inventory-identity">
            <div>
              <div className="report-card-title">{data.member.name ?? "N/A"}</div>
              <div className="order-card-meta">
                Service ID {data.member.sId}
                {data.member.rankName ? <> &middot; {data.member.rankName}</> : null}
                {data.member.unitName ? <> &middot; {data.member.unitName}</> : null}
              </div>
            </div>
            <div className="inventory-identity-totals">
              <div className="shop-board-badge">
                <i className="fa fa-cubes" aria-hidden="true"></i> {data.itemTotal} item(s)
              </div>
              <div className="shop-board-badge">
                <i className="fa fa-clipboard" aria-hidden="true"></i> {data.orderTotal} order(s)
              </div>
            </div>
          </div>

          <InventoryDetailFilters filters={data.filters} />

          <div className="orders-search-result">
            Showing <strong>{periodLabel}</strong>.
          </div>

          {data.groups.length === 0 ? (
            <div className="alert alert-info">
              No uniform ordered by this member for <strong>{periodLabel}</strong>. Counted as 0.
            </div>
          ) : (
            data.groups.map((group) => (
              <div key={group.uniformId} className="order-card">
                <div className="order-card-header">
                  <div>
                    <div className="report-card-title">{group.label}</div>
                    <div className="order-card-meta">
                      {group.itemCount} item(s) across {group.orderCount} order(s)
                    </div>
                  </div>
                </div>

                {group.orders.map((order) => (
                  <div key={order.id} className="inventory-order">
                    <div className="inventory-order-head">
                      <span className="inventory-order-ref">Order #{order.id}</span>
                      <span className={`status-badge status-${order.status}`}>{order.statusLabel}</span>
                      <span className="order-card-meta">
                        Ordered {formatDate(order.createdAt)}
                        {order.collectionDate ? <> &middot; Collection {formatDate(order.collectionDate)}</> : null}
                        {" "}&middot; {order.itemCount} item(s)
                      </span>
                    </div>

                    {order.remarks && order.remarks.trim() !== "" && (
                      <div className="order-card-meta">Remarks: {order.remarks}</div>
                    )}

                    <div className="table-responsive">
                      <table className="table table-orders">
                        <thead>
                          <tr>
                            <th>Clothing Item</th>
                            <th>Size</th>
                            <th>Quantity</th>
                          </tr>
                        </thead>
                        <tbody>
                          {order.items.length > 0 ? (
                            order.items.map((item, i) => (
                              <tr key={i}>
                                <td data-label="Clothing Item">{item.clothes}</td>
                                <td data-label="Size">{item.size || "-"}</td>
                                <td data-label="Quantity">{Math.max(1, item.quantity)}</td>
                              </tr>
                            ))
                          ) : (
                            <tr>
                              <td data-label="Clothing Item" colSpan={3}>
                                No clothing lines recorded on this order.
                              </td>
                            </tr>
                          )}
                        </tbody>
                      </table>
                    </div>
                  </div>
                ))}
              </div>
            ))
          )}
        </div>
      </div>
    </>
  );
}
