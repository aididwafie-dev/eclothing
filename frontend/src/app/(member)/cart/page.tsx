import { redirect } from "next/navigation";
import { LaravelApiError, laravelFetchAuthed } from "@/lib/laravel";
import type { CartResponse, UniformsResponse } from "@/lib/types";
import CartPageClient from "./CartPageClient";

export default async function CartPage() {
  let uniforms: UniformsResponse;
  let cart: CartResponse;
  try {
    [uniforms, cart] = await Promise.all([
      laravelFetchAuthed<UniformsResponse>("/uniforms"),
      laravelFetchAuthed<CartResponse>("/cart"),
    ]);
  } catch (err) {
    if (err instanceof LaravelApiError) {
      if (err.status === 401) {
        redirect("/login");
      }
      // 422: personal details not complete yet -- surfaced in the page below.
      return (
        <div className="rounded-lg border border-black/10 bg-white p-6">
          <h1 className="text-xl font-semibold">Order Uniform</h1>
          <p className="mt-4 text-sm text-red-600">
            {(err.body as { message?: string } | null)?.message ??
              "Please complete your personal details first, before ordering uniform."}
          </p>
        </div>
      );
    }
    throw err;
  }

  return <CartPageClient initialUniforms={uniforms.uniforms} initialCart={cart} />;
}
