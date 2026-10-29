import { adminLoad } from "@/lib/adminPage";
import type { AdminUserOrder } from "@/lib/types";
import UserOrdersClient from "./UserOrdersClient";

/** uniformDetails_eachUser.blade.php -- every order a member has placed. */
export default async function UserUniformDetailsPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const { orders } = await adminLoad<{ orders: AdminUserOrder[] }>(`/admin/users/${id}/orders`);

  return (
    <>
      <br />
      <div className="title">
        <i className="fa fa-black-tie" aria-hidden="true"></i> Uniform Orders
      </div>
      <hr />
      <UserOrdersClient userId={id} initialOrders={orders} />
    </>
  );
}
