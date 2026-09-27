<?php

namespace App\Services;

use App\Exceptions\OrderNotEditableException;
use App\Models\Order;
use App\Models\Ordered_clothe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts a cart (grouped by uniform, then by clothes_slug) into
 * Orders + Ordered_clothes rows -- extracted from
 * DashboardController::checkoutUniformCart's upsert loop so the
 * mobile API's CartController::checkout can share the exact same
 * order-creation rules on top of its own (DB-backed, not session)
 * cart storage.
 *
 * $cartByUniform shape: [uniforms_id => [clothes_slug => ['clothes_slug' => string, 'size' => mixed], ...], ...]
 */
class OrderCheckoutService
{
    public function __construct(private OrderStatusService $orderStatus)
    {
    }

    /**
     * Every checkout places a new order per uniform in the cart, except for
     * uniforms the member loaded into the cart through Edit: those save onto
     * the order being edited, and the cart becomes that whole order, so a
     * line removed from the cart is removed from the order too.
     *
     * @param  array<int|string, int|string> $editOrders uniforms_id => order id being edited
     * @return array<int, int> the order ids written, keyed by uniforms_id
     * @throws \App\Exceptions\OrderNotEditableException when an edited order
     *         has already left Pending.
     */
    public function checkoutForUser(int $userId, array $cartByUniform, array $editOrders = []): array
    {
        // Checked up front, before anything is written, so a cart spanning
        // several uniforms cannot be half-applied: either every edited order
        // is still editable or the whole checkout is refused.
        $editedOrders = $this->editedOrders($userId, $cartByUniform, $editOrders);

        $orderIds = [];

        foreach ($cartByUniform as $uniformsId => $items) {
            if (!is_array($items) || !count($items)) {
                continue;
            }

            $edited = $editedOrders[(int) $uniformsId] ?? null;
            $orderId = $edited
                ? $this->reopenOrder($edited)
                : $this->createOrder($userId, (int) $uniformsId);

            foreach ($items as $item) {
                $this->upsertOrderedCloth($orderId, (int) $uniformsId, $item);
            }

            if ($edited) {
                DB::table('ordered_clothes')
                    ->where('order_id', '=', $orderId)
                    ->whereNotIn('clothes_slug', array_map(fn ($item) => (string) $item['clothes_slug'], array_values($items)))
                    ->delete();
            }

            $orderIds[(int) $uniformsId] = $orderId;
        }

        return $orderIds;
    }

    /**
     * The orders this checkout edits, keyed by uniforms_id. An edit whose
     * order has since been deleted is dropped, so that uniform is placed as
     * a new order instead.
     *
     * @return array<int, object>
     */
    private function editedOrders(int $userId, array $cartByUniform, array $editOrders): array
    {
        $edited = [];
        $blocked = [];

        foreach ($editOrders as $uniformsId => $orderId) {
            $items = $cartByUniform[$uniformsId] ?? null;
            if (!is_array($items) || !count($items)) {
                continue;
            }

            $order = DB::table('orders')
                ->where('id', '=', (int) $orderId)
                ->where('user_id', '=', $userId)
                ->where('uniforms_id', '=', (int) $uniformsId)
                ->where('deleted', '=', 0)
                ->first();

            if (!$order) {
                continue;
            }

            if ($this->orderStatus->isOrderEditable($order->status ?? null)) {
                $edited[(int) $uniformsId] = $order;
                continue;
            }

            $uniform = DB::table('uniforms')->where('id', '=', (int) $uniformsId)->first();
            // uniform_type is often a bare numeric code, so prefer the
            // readable name when the row carries one.
            $label = trim((string) ($uniform->uniform_name ?? '')) !== ''
                ? trim((string) $uniform->uniform_name)
                : trim((string) ($uniform->uniform_type ?? ''));

            $blocked[] = [
                'uniform' => $label !== '' ? $label : ('uniform #' . (int) $uniformsId),
                'status' => $this->orderStatus->orderStatusMeta($order->status ?? null)['label'],
            ];
        }

        if ($blocked) {
            throw new OrderNotEditableException($blocked);
        }

        return $edited;
    }

    private function createOrder(int $userId, int $uniformsId): int
    {
        $order = new Order;
        $order->user_id = $userId;
        $order->uniforms_id = $uniformsId;
        if ($this->orderStatus->hasOrderLifecycleColumns()) {
            $order->status = '1';
            $order->remarks = null;
            $order->collection_date = null;
        }
        $order->save();

        return $order->id;
    }

    private function reopenOrder(object $order): int
    {
        if ($this->orderStatus->hasOrderLifecycleColumns()) {
            DB::table('orders')->where('id', '=', $order->id)->update([
                'status' => '1',
                'remarks' => null,
                'collection_date' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            DB::table('orders')->where('id', '=', $order->id)->update(['updated_at' => date('Y-m-d H:i:s')]);
        }

        // The member has changed what they asked for, so any quantities an
        // officer granted earlier no longer apply.
        if (Schema::hasColumn('ordered_clothes', 'approved_quantity')) {
            DB::table('ordered_clothes')->where('order_id', '=', $order->id)->update(['approved_quantity' => null]);
        }

        return (int) $order->id;
    }

    private function upsertOrderedCloth(int $orderId, int $uniformsId, array $item): void
    {
        $cloth = DB::table('uniform_clothes')
            ->select('clothes_type')
            ->where('uniforms_id', '=', $uniformsId)
            ->where('clothes_slug', '=', $item['clothes_slug'])
            ->first();

        if (!$cloth) {
            return;
        }

        $sizeValue = $item['size'];
        if (is_array($sizeValue)) {
            $sizeValue = implode(',', $sizeValue);
        }

        $existing = DB::table('ordered_clothes')
            ->where('order_id', '=', $orderId)
            ->where('clothes_slug', '=', $item['clothes_slug'])
            ->first();

        // Carts written before the quantity column existed have no quantity
        // key; those rows are one piece each, matching the old behaviour.
        $quantity = isset($item['quantity']) ? max(1, (int) $item['quantity']) : 1;
        $hasQuantityColumn = $this->orderedClothesHasQuantity();

        if ($existing) {
            $orderedCloth = Ordered_clothe::find($existing->id);
            $orderedCloth->size = $sizeValue;
            if ($hasQuantityColumn) {
                $orderedCloth->quantity = $quantity;
            }
            $orderedCloth->save();
        } else {
            $orderedCloth = new Ordered_clothe;
            $orderedCloth->order_id = $orderId;
            $orderedCloth->clothes = $cloth->clothes_type;
            $orderedCloth->clothes_slug = $item['clothes_slug'];
            $orderedCloth->size = $sizeValue;
            if ($hasQuantityColumn) {
                $orderedCloth->quantity = $quantity;
            }
            $orderedCloth->save();
        }
    }

    private function orderedClothesHasQuantity(): bool
    {
        static $has = null;

        if ($has === null) {
            try {
                $has = Schema::hasColumn('ordered_clothes', 'quantity');
            } catch (\Throwable $e) {
                $has = false;
            }
        }

        return $has;
    }
}
