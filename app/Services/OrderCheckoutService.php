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
     * @throws \App\Exceptions\OrderNotEditableException when the cart would
     *         overwrite an order that has already left Pending.
     */
    public function checkoutForUser(int $userId, array $cartByUniform): void
    {
        // Checked up front, before anything is written, so a cart spanning
        // several uniforms cannot be half-applied: either every affected
        // order is editable or the whole checkout is refused.
        $this->assertOrdersAreEditable($userId, $cartByUniform);

        foreach ($cartByUniform as $uniformsId => $items) {
            if (!is_array($items) || !count($items)) {
                continue;
            }

            $orderId = $this->resolveOrderId($userId, (int) $uniformsId);

            foreach ($items as $item) {
                $this->upsertOrderedCloth($orderId, (int) $uniformsId, $item);
            }
        }
    }

    private function assertOrdersAreEditable(int $userId, array $cartByUniform): void
    {
        if (!$this->orderStatus->hasOrderLifecycleColumns()) {
            return;
        }

        $blocked = [];

        foreach ($cartByUniform as $uniformsId => $items) {
            if (!is_array($items) || !count($items)) {
                continue;
            }

            $existing = DB::table('orders')
                ->where('deleted', '=', 0)
                ->where('user_id', '=', $userId)
                ->where('uniforms_id', '=', (int) $uniformsId)
                ->first();

            // No existing order means this checkout creates a fresh one,
            // which is always allowed.
            if (!$existing || $this->orderStatus->isOrderEditable($existing->status ?? null)) {
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
                'status' => $this->orderStatus->orderStatusMeta($existing->status ?? null)['label'],
            ];
        }

        if ($blocked) {
            throw new OrderNotEditableException($blocked);
        }
    }

    private function resolveOrderId(int $userId, int $uniformsId): int
    {
        $userOrder = DB::table('orders')
            ->where('deleted', '=', 0)
            ->where('user_id', '=', $userId)
            ->where('uniforms_id', '=', $uniformsId)
            ->first();

        if (!$userOrder) {
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

        if ($this->orderStatus->hasOrderLifecycleColumns()) {
            DB::table('orders')->where('id', '=', $userOrder->id)->update([
                'status' => '1',
                'remarks' => null,
                'collection_date' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $userOrder->id;
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
