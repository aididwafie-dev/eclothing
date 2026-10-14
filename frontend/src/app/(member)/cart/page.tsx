import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";
import type { CartResponse, OrdersResponse, UniformsResponse } from "@/lib/types";
import CartPageClient from "./CartPageClient";

export default async function CartPage({
  searchParams,
}: {
  searchParams: Promise<{ uniform?: string }>;
}) {
  const { uniform } = await searchParams;

  let uniforms: UniformsResponse;
  let cart: CartResponse;
  let orders: OrdersResponse;
  try {
    [uniforms, cart, orders] = await Promise.all([
      laravelFetchAuthed<UniformsResponse>("/uniforms"),
      laravelFetchAuthed<CartResponse>("/cart"),
      laravelFetchAuthed<OrdersResponse>("/orders"),
    ]);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      if (err.status === 401) {
        redirect("/login");
      }
      // 422: personal details not complete yet -- surfaced in the page below.
      return (
        <>
          <div className="title">
            <i className="fa fa-shirtsinbulk" aria-hidden="true"></i> Order Uniform
          </div>
          <hr />
          <div className="containerMain">
            <div className="content">
              <p className="text-danger">
                {(err.body as { message?: string } | null)?.message ??
                  "Please complete your personal details first, before ordering uniform."}
              </p>
            </div>
          </div>
        </>
      );
    }
    throw err;
  }

  // Uniform id -> order id, for every order the member can still edit --
  // mirrors uniform_selection.blade.php's $editOrders, sourced from the
  // orders list rather than a session value since the API has no session.
  const editOrders: Record<string, string> = {};
  for (const order of orders.orders) {
    if (order.editable) {
      editOrders[order.uniformsId] = order.id;
    }
  }

  return (
    <CartPageClient
      initialUniforms={uniforms.uniforms}
      initialCart={cart}
      editOrders={editOrders}
      requestedUniformId={uniform ?? null}
    />
  );
}
