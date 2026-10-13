"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import type { CartResponse, ClothesItem, UniformOption } from "@/lib/types";

export default function CartPageClient({
  initialUniforms,
  initialCart,
}: {
  initialUniforms: UniformOption[];
  initialCart: CartResponse;
}) {
  const router = useRouter();
  const activeUniforms = initialUniforms.filter((u) => u.active);

  const [selectedUniformId, setSelectedUniformId] = useState(activeUniforms[0]?.id ?? "");
  const [clothes, setClothes] = useState<ClothesItem[]>([]);
  // Derived, not stored -- stays in sync without an extra setState at the
  // top of the effect below (the clothes list for the previous uniform
  // selection stays stale/empty until the fetch for the new one resolves).
  const [loadedUniformId, setLoadedUniformId] = useState<string | null>(null);
  const loadingClothes = loadedUniformId !== selectedUniformId;
  const [cart, setCart] = useState(initialCart);
  const [checkoutMessage, setCheckoutMessage] = useState<{ kind: "ok" | "error"; text: string } | null>(
    null,
  );
  const [checkingOut, setCheckingOut] = useState(false);

  useEffect(() => {
    if (!selectedUniformId) {
      return;
    }
    fetch(`/api/uniforms/${selectedUniformId}/clothes`)
      .then((res) => res.json())
      .then((data) => {
        setClothes(data.clothes ?? []);
        setLoadedUniformId(selectedUniformId);
      });
  }, [selectedUniformId]);

  async function addToCart(item: ClothesItem, size: unknown) {
    const res = await fetch("/api/cart/add", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ uniformsId: selectedUniformId, clothesSlug: item.clothesSlug, size }),
    });
    const data = await res.json();
    if (res.ok) {
      setCart(data);
      setClothes((prev) =>
        prev.map((c) => (c.clothesSlug === item.clothesSlug ? { ...c, inCart: true, cartValue: size } : c)),
      );
    }
  }

  async function removeFromCart(item: ClothesItem) {
    const res = await fetch("/api/cart/remove", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ uniformsId: selectedUniformId, clothesSlug: item.clothesSlug }),
    });
    const data = await res.json();
    if (res.ok) {
      setCart(data);
      setClothes((prev) =>
        prev.map((c) => (c.clothesSlug === item.clothesSlug ? { ...c, inCart: false, cartValue: null } : c)),
      );
    }
  }

  async function handleCheckout() {
    setCheckingOut(true);
    setCheckoutMessage(null);
    try {
      const res = await fetch("/api/cart/checkout", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: "{}",
      });
      const data = await res.json();
      if (!res.ok) {
        setCheckoutMessage({ kind: "error", text: data.message ?? "Could not check out." });
        return;
      }
      router.push("/orders");
      router.refresh();
    } catch {
      setCheckoutMessage({ kind: "error", text: "Could not reach the server." });
    } finally {
      setCheckingOut(false);
    }
  }

  return (
    <div className="flex flex-col gap-6 lg:flex-row">
      <div className="flex-1 rounded-lg border border-black/10 bg-white p-6">
        <h1 className="text-xl font-semibold">Order Uniform</h1>

        <div className="mt-4">
          <label className="block text-sm font-medium">Uniform</label>
          <select
            value={selectedUniformId}
            onChange={(e) => setSelectedUniformId(e.target.value)}
            className="mt-1 w-full max-w-sm rounded-md border border-black/15 px-3 py-2 text-sm"
          >
            {activeUniforms.map((u) => (
              <option key={u.id} value={u.id}>
                {u.uniformName ?? u.uniformType}
              </option>
            ))}
          </select>
        </div>

        {loadingClothes ? (
          <p className="mt-6 text-sm text-black/50">Loading...</p>
        ) : (
          <div className="mt-6 flex flex-col gap-4">
            {clothes.map((item) => (
              <ClothesRow key={item.clothesSlug} item={item} onAdd={addToCart} onRemove={removeFromCart} />
            ))}
          </div>
        )}
      </div>

      <div className="w-full rounded-lg border border-black/10 bg-white p-6 lg:w-80">
        <h2 className="font-semibold">Cart ({cart.count})</h2>
        <ul className="mt-3 flex flex-col gap-2 text-sm">
          {cart.items.map((item) => (
            <li key={item.clothesSlug} className="border-b border-black/5 pb-2">
              <div className="font-medium">{item.clothesType}</div>
              <div className="text-black/50">
                {item.uniformName} &mdash; {JSON.stringify(item.size)}
              </div>
            </li>
          ))}
          {cart.items.length === 0 && <li className="text-black/50">Cart is empty.</li>}
        </ul>
        <button
          onClick={handleCheckout}
          disabled={checkingOut || cart.count === 0}
          className="mt-4 w-full rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-black/80 disabled:opacity-50"
        >
          {checkingOut ? "Placing order..." : "Checkout"}
        </button>
        {checkoutMessage && (
          <p className={checkoutMessage.kind === "ok" ? "mt-2 text-sm text-green-700" : "mt-2 text-sm text-red-600"}>
            {checkoutMessage.text}
          </p>
        )}
      </div>
    </div>
  );
}

function ClothesRow({
  item,
  onAdd,
  onRemove,
}: {
  item: ClothesItem;
  onAdd: (item: ClothesItem, size: unknown) => void;
  onRemove: (item: ClothesItem) => void;
}) {
  const [value, setValue] = useState<string>(typeof item.cartValue === "string" ? item.cartValue : "");
  const [selected, setSelected] = useState<string[]>(Array.isArray(item.cartValue) ? item.cartValue : []);

  return (
    <div className="flex items-center justify-between gap-4 border-b border-black/5 pb-3">
      <div className="font-medium">{item.clothesType}</div>

      <div className="flex items-center gap-2">
        {item.sizeKind === "toggle" && (
          <label className="flex items-center gap-1 text-sm">
            <input
              type="checkbox"
              checked={item.inCart}
              onChange={(e) => (e.target.checked ? onAdd(item, "YES") : onRemove(item))}
            />
            Add this item
          </label>
        )}

        {item.sizeKind === "fixed" &&
          (item.inCart ? (
            <button onClick={() => onRemove(item)} className="text-sm text-red-600">
              Remove
            </button>
          ) : (
            <button onClick={() => onAdd(item, "FIX")} className="rounded-md border border-black/15 px-3 py-1 text-sm">
              Add
            </button>
          ))}

        {item.sizeKind === "text" && (
          <>
            <input
              type="text"
              value={value}
              onChange={(e) => setValue(e.target.value)}
              className="w-24 rounded-md border border-black/15 px-2 py-1 text-sm"
            />
            <button
              onClick={() => onAdd(item, value)}
              className="rounded-md border border-black/15 px-3 py-1 text-sm"
            >
              Add
            </button>
          </>
        )}

        {item.sizeKind === "select" && (
          <>
            <select
              value={value}
              onChange={(e) => setValue(e.target.value)}
              className="rounded-md border border-black/15 px-2 py-1 text-sm"
            >
              <option value="">Choose your size....</option>
              {item.sizeOptions.map((opt) => (
                <option key={opt} value={opt}>
                  {opt}
                </option>
              ))}
            </select>
            <button
              onClick={() => onAdd(item, value)}
              className="rounded-md border border-black/15 px-3 py-1 text-sm"
            >
              Add
            </button>
          </>
        )}

        {item.sizeKind === "multiselect" && (
          <>
            <div className="flex flex-wrap gap-2">
              {item.sizeOptions.map((opt) => (
                <label key={opt} className="flex items-center gap-1 text-sm">
                  <input
                    type="checkbox"
                    checked={selected.includes(opt)}
                    onChange={(e) =>
                      setSelected((prev) =>
                        e.target.checked ? [...prev, opt] : prev.filter((v) => v !== opt),
                      )
                    }
                  />
                  {opt}
                </label>
              ))}
            </div>
            <button
              onClick={() => onAdd(item, selected)}
              className="rounded-md border border-black/15 px-3 py-1 text-sm"
            >
              Add
            </button>
          </>
        )}

        {item.inCart && item.sizeKind !== "fixed" && item.sizeKind !== "toggle" && (
          <button onClick={() => onRemove(item)} className="text-sm text-red-600">
            Remove
          </button>
        )}
      </div>
    </div>
  );
}
