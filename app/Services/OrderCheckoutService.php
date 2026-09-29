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
     * One checkout is one order: every uniform in the cart goes onto it,
     * each line recording its own uniform. When the member is editing an
     * order, the cart is saved back onto that order instead and becomes the
     * whole of it, so a line removed from the cart is removed from the order.
     *
     * @param  int|null $editOrderId the order loaded into the cart through Edit
     * @return int|null the order written, or null when the cart held nothing
     * @throws \App\Exceptions\OrderNotEditableException when the edited order
     *         has already left Pending.
     */
    public function checkoutForUser(int $userId, array $cartByUniform, ?int $editOrderId = null): ?int
    {
        $lines = [];
        foreach ($cartByUniform as $uniformsId => $items) {
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                if (is_array($item) && !empty($item['clothes_slug'])) {
                    $lines[] = [(int) $uniformsId, $item];
                }
            }
        }
        if (!$lines) {
            return null;
        }

        // Checked before anything is written, so a refused edit leaves the
        // order exactly as it was.
        $edited = $this->editedOrder($userId, $editOrderId);

        $orderId = $edited
            ? $this->reopenOrder($edited)
            : $this->createOrder($userId, $lines[0][0]);

        $kept = [];
        foreach ($lines as [$uniformsId, $item]) {
            if ($this->upsertOrderedCloth($orderId, $uniformsId, $item)) {
                $kept[] = $uniformsId . '|' . $item['clothes_slug'];
            }
        }

        if ($edited) {
            foreach (DB::table('ordered_clothes')->where('order_id', '=', $orderId)->get() as $line) {
                if (!in_array($this->lineUniformId($line, $edited) . '|' . $line->clothes_slug, $kept, true)) {
                    DB::table('ordered_clothes')->where('id', '=', $line->id)->delete();
                }
            }
        }

        return $orderId;
    }

    /**
     * The order being edited, if it is still the member's and still there.
     * One that has since been deleted is ignored, so the cart is placed as a
     * new order instead.
     */
    private function editedOrder(int $userId, ?int $editOrderId): ?object
    {
        if (!$editOrderId) {
            return null;
        }

        $order = DB::table('orders')
            ->where('id', '=', $editOrderId)
            ->where('user_id', '=', $userId)
            ->where('deleted', '=', 0)
            ->first();

        if (!$order) {
            return null;
        }

        if (!$this->orderStatus->isOrderEditable($order->status ?? null)) {
            throw new OrderNotEditableException([[
                'uniform' => 'Order #' . (int) $order->id,
                'status' => $this->orderStatus->orderStatusMeta($order->status ?? null)['label'],
            ]]);
        }

        return $order;
    }

    /**
     * A line's uniform: its own, or its order's for a line written before
     * lines carried one.
     */
    private function lineUniformId(object $line, object $order): int
    {
        return (int) (!empty($line->uniforms_id) ? $line->uniforms_id : $order->uniforms_id);
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

    /**
     * Adds or updates one cart line on the order. False when the item is no
     * longer offered for that uniform, so it is left off.
     */
    private function upsertOrderedCloth(int $orderId, int $uniformsId, array $item): bool
    {
        $cloth = DB::table('uniform_clothes')
            ->select('clothes_type')
            ->where('uniforms_id', '=', $uniformsId)
            ->where('clothes_slug', '=', $item['clothes_slug'])
            ->first();

        if (!$cloth) {
            return false;
        }

        $sizeValue = $item['size'];
        if (is_array($sizeValue)) {
            $sizeValue = implode(',', $sizeValue);
        }

        $hasUniformColumn = $this->orderedClothesHasUniform();
        $existingQuery = DB::table('ordered_clothes')
            ->where('order_id', '=', $orderId)
            ->where('clothes_slug', '=', $item['clothes_slug']);
        if ($hasUniformColumn) {
            $existingQuery->where(function ($q) use ($uniformsId) {
                $q->where('uniforms_id', '=', $uniformsId)->orWhereNull('uniforms_id');
            });
        }
        $existing = $existingQuery->first();

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
            if ($hasUniformColumn) {
                $orderedCloth->uniforms_id = $uniformsId;
            }
            $orderedCloth->save();
        } else {
            $orderedCloth = new Ordered_clothe;
            $orderedCloth->order_id = $orderId;
            if ($hasUniformColumn) {
                $orderedCloth->uniforms_id = $uniformsId;
            }
            $orderedCloth->clothes = $cloth->clothes_type;
            $orderedCloth->clothes_slug = $item['clothes_slug'];
            $orderedCloth->size = $sizeValue;
            if ($hasQuantityColumn) {
                $orderedCloth->quantity = $quantity;
            }
            $orderedCloth->save();
        }

        return true;
    }

    private function orderedClothesHasUniform(): bool
    {
        static $has = null;

        if ($has === null) {
            try {
                $has = Schema::hasColumn('ordered_clothes', 'uniforms_id');
            } catch (\Throwable $e) {
                $has = false;
            }
        }

        return $has;
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
