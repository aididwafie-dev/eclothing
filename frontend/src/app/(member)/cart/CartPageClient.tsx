"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import type { CartResponse, ClothesItem, UniformOption } from "@/lib/types";

export default function CartPageClient({
  initialUniforms,
  initialCart,
  editOrders,
  requestedUniformId,
}: {
  initialUniforms: UniformOption[];
  initialCart: CartResponse;
  editOrders: Record<string, string>;
  requestedUniformId: string | null;
}) {
  const router = useRouter();
  const activeUniforms = initialUniforms.filter((u) => u.active);

  const initialSelected =
    requestedUniformId && activeUniforms.some((u) => u.id === requestedUniformId)
      ? requestedUniformId
      : activeUniforms[0]?.id ?? "";

  const [selectedUniformId, setSelectedUniformId] = useState(initialSelected);
  const [clothes, setClothes] = useState<ClothesItem[]>([]);
  // Derived, not stored -- stays in sync without an extra setState at the
  // top of the effect below (the clothes list for the previous uniform
  // selection stays stale/empty until the fetch for the new one resolves).
  const [loadedUniformId, setLoadedUniformId] = useState<string | null>(null);
  const loadingClothes = loadedUniformId !== selectedUniformId;
  const [cart, setCart] = useState(initialCart);
  const [checkoutMessage, setCheckoutMessage] = useState<string | null>(null);
  const [checkingOut, setCheckingOut] = useState(false);

  const editOrderId = editOrders[selectedUniformId] ?? null;

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

  async function addToCart(item: ClothesItem, size: unknown, quantity: number) {
    const res = await fetch("/api/cart/add", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ uniformsId: selectedUniformId, clothesSlug: item.clothesSlug, size, quantity }),
    });
    const data = await res.json();
    if (res.ok) {
      setCart(data);
      setClothes((prev) =>
        prev.map((c) => (c.clothesSlug === item.clothesSlug ? { ...c, inCart: true, cartValue: size, quantity } : c)),
      );
    }
  }

  async function removeFromCart(uniformsId: string, clothesSlug: string) {
    const res = await fetch("/api/cart/remove", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ uniformsId, clothesSlug }),
    });
    const data = await res.json();
    if (res.ok) {
      setCart(data);
      if (uniformsId === selectedUniformId) {
        setClothes((prev) =>
          prev.map((c) => (c.clothesSlug === clothesSlug ? { ...c, inCart: false, cartValue: null } : c)),
        );
      }
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
        setCheckoutMessage(data.message ?? "Sorry, your order could not be saved. Please try again.");
        return;
      }
      router.push("/orders");
      router.refresh();
    } catch {
      setCheckoutMessage("Could not reach the server.");
    } finally {
      setCheckingOut(false);
    }
  }

  const selectedUniform = activeUniforms.find((u) => u.id === selectedUniformId);
  const uniformDisplayName = selectedUniform
    ? selectedUniform.uniformName ?? selectedUniform.uniformType
    : "";
  const currentUniformCartCount = clothes.filter((c) => c.inCart).length;

  return (
    <>
      <div className="title">
        <i className="fa fa-shirtsinbulk" aria-hidden="true"></i> Order Uniform
      </div>
      <hr />

      <div className="containerMain">
        <div className="content">
          <div className="cart-board-header">
            <div className="title">
              <i className="fa fa-shopping-cart" aria-hidden="true"></i> Uniform Shopping Cart
            </div>
            {editOrderId && (
              <div className="cart-order-badge">
                <i className="fa fa-pencil" aria-hidden="true"></i>
                <span>
                  Order ID <strong>#{editOrderId}</strong>
                </span>
              </div>
            )}
          </div>
          <hr />

          <div className="uniform-picker">
            {activeUniforms.map((u) => (
              <a
                key={u.id}
                className={`uniform-pill uniform-select${u.id === selectedUniformId ? " active" : ""}`}
                onClick={() => setSelectedUniformId(u.id)}
              >
                <span className="uniform-pill-title">
                  {u.uniformType !== "ACC" ? `${u.uniformType}${u.uniformName ? ` (${u.uniformName})` : ""}` : u.uniformType}
                </span>
              </a>
            ))}
          </div>

          {loadingClothes ? (
            <div className="text-center" style={{ color: "var(--text)", marginTop: "2em" }}>
              <i className="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
              <br />
              <span style={{ fontSize: "125%" }}>Loading items....</span>
            </div>
          ) : (
            <div className="shop-cart-layout">
              <div className="shop-board">
                <div className="shop-board-header">
                  <div>
                    <div className="shop-board-title">Uniform Shopping Cart</div>
                    <div className="shop-board-subtitle">
                      {uniformDisplayName} &middot; {currentUniformCartCount} selected in this uniform &middot; {cart.count} items total
                    </div>
                  </div>
                  <div className="shop-board-badge">
                    <i className="fa fa-shirtsinbulk" aria-hidden="true"></i> {uniformDisplayName}
                  </div>
                </div>

                <div className="shop-board-columns">
                  <div>Item</div>
                  <div>Selection</div>
                  <div>Status</div>
                  <div>Action</div>
                </div>

                <div className="shop-board-list">
                  {clothes.map((item) => (
                    <ClothesRow
                      key={item.clothesSlug}
                      item={item}
                      uniformDisplayName={uniformDisplayName}
                      onAdd={addToCart}
                      onRemove={() => removeFromCart(selectedUniformId, item.clothesSlug)}
                    />
                  ))}
                </div>

                <div className="shop-board-footer">
                  <div className="shop-policy">
                    <i className="fa fa-check-square" aria-hidden="true"></i>
                    <span>Review the selected size or option before submitting your uniform order.</span>
                  </div>
                </div>
              </div>

              <div className="shop-summary-card">
                <div className="shop-summary-head">
                  <div className="shop-summary-title">Cart Summary</div>
                  <div className="shop-summary-subtitle">Updates automatically after each `+` or `-` action.</div>
                </div>

                <div className="shop-summary-totals">
                  <div className="shop-summary-total-box">
                    <div className="shop-summary-total-label">This Uniform</div>
                    <div className="shop-summary-total-value">{currentUniformCartCount}</div>
                  </div>
                  <div className="shop-summary-total-box">
                    <div className="shop-summary-total-label">All Cart Items</div>
                    <div className="shop-summary-total-value">{cart.count}</div>
                  </div>
                </div>

                {cart.items.length > 0 ? (
                  <div className="shop-cart-preview shop-cart-preview-side">
                    <div className="shop-cart-preview-title">Current Cart</div>
                    <div className="shop-cart-preview-list">
                      {cart.items.map((item) => (
                        <div key={`${item.uniformsId}-${item.clothesSlug}`} className="shop-cart-preview-row">
                          <div className="shop-cart-preview-main">
                            <div className="shop-cart-preview-name">{item.clothesType}</div>
                            <div className="shop-cart-preview-meta">
                              {Array.isArray(item.size) ? item.size.join(", ") : String(item.size ?? "")}
                            </div>
                          </div>
                          <span className="shop-cart-preview-qty">&times; {item.quantity ?? 1}</span>
                          <a
                            className="shop-cart-remove cart-remove"
                            onClick={() => removeFromCart(item.uniformsId, item.clothesSlug)}
                            aria-label="Remove"
                          >
                            <i className="fa fa-times" aria-hidden="true"></i>
                          </a>
                        </div>
                      ))}
                    </div>
                  </div>
                ) : (
                  <div className="shop-summary-empty">No items in cart yet. Use the `+` icon to add items.</div>
                )}

                <div className="shop-summary-footer">
                  <a
                    className={`btn btn-brand shop-checkout-btn cart-checkout${cart.count && !checkingOut ? "" : " disabled"}`}
                    onClick={() => {
                      if (cart.count && !checkingOut) {
                        void handleCheckout();
                      }
                    }}
                  >
                    {editOrderId ? "Update Order" : "Order Now"} <i className="fa fa-arrow-right" aria-hidden="true"></i>
                  </a>
                </div>
                {checkoutMessage && <p className="text-danger">{checkoutMessage}</p>}
              </div>
            </div>
          )}
        </div>
      </div>
    </>
  );
}

function ClothesRow({
  item,
  uniformDisplayName,
  onAdd,
  onRemove,
}: {
  item: ClothesItem;
  uniformDisplayName: string;
  onAdd: (item: ClothesItem, size: unknown, quantity: number) => void;
  onRemove: () => void;
}) {
  const [value, setValue] = useState<string>(typeof item.cartValue === "string" ? item.cartValue : "");
  const [selected, setSelected] = useState<string[]>(Array.isArray(item.cartValue) ? item.cartValue : []);
  const [checked, setChecked] = useState(item.inCart);
  const [qty, setQty] = useState(item.quantity || 1);

  function currentSize(): unknown {
    if (item.sizeKind === "toggle") return checked ? "YES" : "";
    if (item.sizeKind === "fixed") return item.clothesSize ?? "FIX";
    if (item.sizeKind === "multiselect") return selected;
    return value;
  }

  const hasPreviousSize = !!item.orderedSize;

  return (
    <div className="shop-line">
      <div className="shop-line-item">
        <div className="shop-line-thumb-wrap">
          {item.imageUrl && <img className="shop-line-thumb" src={item.imageUrl} alt={item.clothesType} />}
        </div>
        <div className="shop-line-copy">
          <div className="shop-line-name">{item.clothesType}</div>
          <div className="shop-line-meta">
            {item.accessory ? "Accessory" : "Clothes"} for {uniformDisplayName}
          </div>
          {hasPreviousSize ? (
            <div className="shop-line-hint">Previous saved size: {item.orderedSize}</div>
          ) : item.clothesSize ? (
            <div className="shop-line-hint">Available rule: {item.clothesSize}</div>
          ) : (
            <div className="shop-line-hint">No size needed for this item</div>
          )}
        </div>
      </div>

      <div className="shop-line-selection">
        <div className="shop-controls">
          {item.sizeKind === "toggle" && (
            <div className="shop-checkbox">
              <input
                className="form-check-input"
                type="checkbox"
                checked={checked}
                onChange={(e) => setChecked(e.target.checked)}
              />
              <label className="form-check-label">Add this item</label>
            </div>
          )}

          {item.sizeKind === "fixed" && (
            <>
              <label className="label_">Size</label>
              <input className="form-control" type="text" value={item.clothesSize ?? ""} readOnly />
            </>
          )}

          {item.sizeKind === "text" && (
            <>
              <label className="label_">Size</label>
              <input
                className="form-control"
                type="text"
                placeholder={item.clothesSize ?? ""}
                value={value}
                onChange={(e) => setValue(e.target.value)}
              />
            </>
          )}

          {item.sizeKind === "select" && (
            <>
              <label className="label_">Size</label>
              <select className="form-control" value={value} onChange={(e) => setValue(e.target.value)}>
                <option value="">Choose your size....</option>
                {item.sizeOptions.map((opt) => (
                  <option key={opt} value={opt}>
                    {opt}
                  </option>
                ))}
              </select>
            </>
          )}

          {item.sizeKind === "multiselect" && (
            <>
              <label className="label_">Options</label>
              <select
                className="form-control"
                multiple
                value={selected}
                onChange={(e) => setSelected(Array.from(e.target.selectedOptions, (o) => o.value))}
              >
                {item.sizeOptions.map((opt) => (
                  <option key={opt} value={opt}>
                    {opt}
                  </option>
                ))}
              </select>
            </>
          )}

          <div className="shop-qty-row">
            <label className="label_" style={{ margin: 0 }}>
              Quantity
            </label>
            <input
              className="form-control shop-qty-input"
              type="number"
              min={1}
              step={1}
              max={item.maxQuantity ?? undefined}
              value={qty}
              onChange={(e) => setQty(Math.max(1, parseInt(e.target.value, 10) || 1))}
              aria-label={`Quantity for ${item.clothesType}`}
            />
            {item.maxQuantity !== null && <span className="shop-qty-max">Maximum {item.maxQuantity}</span>}
          </div>
        </div>
      </div>

      <div className="shop-line-status">
        <span className={`shop-status-badge${item.inCart ? " is-in-cart" : ""}`}>
          {item.inCart ? "In Cart" : "Not Added"}
        </span>
        {item.cartValue ? (
          <div className="shop-status-note">
            {Array.isArray(item.cartValue) ? item.cartValue.join(", ") : String(item.cartValue)}
          </div>
        ) : null}
      </div>

      <div className="shop-line-actions">
        <a
          className="shop-line-action-btn shop-line-action-add cart-add"
          aria-label={item.inCart ? "Update item" : "Add item"}
          title={item.inCart ? "Update item" : "Add item"}
          onClick={() => onAdd(item, currentSize(), qty)}
        >
          <i className="fa fa-plus" aria-hidden="true"></i>
        </a>
        <a
          className={`shop-line-action-btn shop-line-action-remove cart-remove${item.inCart ? "" : " disabled"}`}
          aria-label="Remove item"
          title="Remove item"
          onClick={() => {
            if (item.inCart) onRemove();
          }}
        >
          <i className="fa fa-minus" aria-hidden="true"></i>
        </a>
      </div>
    </div>
  );
}
