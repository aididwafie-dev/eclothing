import { adminLoad } from "@/lib/adminPage";
import type { AdminUserOrderDetail } from "@/lib/types";
import EditOrderSizesForm from "./EditOrderSizesForm";

/** uniformDetails_edit.blade.php. */
export default async function EditUserOrderPage({ params }: { params: Promise<{ id: string; orderId: string }> }) {
  const { id, orderId } = await params;
  const { order } = await adminLoad<{ order: AdminUserOrderDetail }>(`/admin/user-orders/${orderId}`);

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-pencil-square" aria-hidden="true"></i> Edit Uniform size of User
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <i className="fa fa-pencil" aria-hidden="true"></i> <b>Uniform: {order.uniform}</b>
          <hr />
          <EditOrderSizesForm userId={id} order={order} />
        </div>
      </div>
    </>
  );
}
