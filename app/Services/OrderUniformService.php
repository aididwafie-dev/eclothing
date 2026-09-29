<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * The uniforms an order holds.
 *
 * One checkout is one order, and a cart can hold items from several
 * uniforms, so an order's uniforms are those of its lines. A line written
 * before lines recorded their uniform belongs to its order's (the order's
 * first uniform, orders.uniforms_id), and so does an order with no lines.
 */
class OrderUniformService
{
    /**
     * SQL for a line's uniform, for queries that join ordered_clothes to
     * orders under those names.
     */
    public const LINE_UNIFORM_SQL = 'COALESCE(ordered_clothes.uniforms_id, orders.uniforms_id)';

    /**
     * Uniform ids per order, in the order they were first added.
     *
     * @param  array<int, int|string> $orderIds
     * @return array<int, array<int, int>> keyed by order id
     */
    public function uniformIdsByOrder(array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));
        if (!$orderIds) {
            return [];
        }

        $result = [];
        $rows = DB::table('orders')
            ->leftJoin('ordered_clothes', 'ordered_clothes.order_id', '=', 'orders.id')
            ->whereIn('orders.id', $orderIds)
            ->orderBy('orders.id')
            ->orderBy('ordered_clothes.id')
            ->selectRaw('orders.id as order_id, ' . self::LINE_UNIFORM_SQL . ' as uniform_id')
            ->get();

        foreach ($rows as $row) {
            $uniformId = (int) $row->uniform_id;
            if ($uniformId > 0 && !in_array($uniformId, $result[(int) $row->order_id] ?? [], true)) {
                $result[(int) $row->order_id][] = $uniformId;
            }
        }

        return $result;
    }

    /**
     * Readable uniform names per order, e.g. "4 (LORENG DIGITAL), 7 (BAJU
     * MELAYU)".
     *
     * @param  array<int, int|string> $orderIds
     * @return array<int, string> keyed by order id
     */
    public function labelsByOrder(array $orderIds): array
    {
        $idsByOrder = $this->uniformIdsByOrder($orderIds);
        $uniforms = $this->uniforms(array_merge([], ...array_values($idsByOrder ?: [[]])));

        $labels = [];
        foreach ($idsByOrder as $orderId => $uniformIds) {
            $labels[$orderId] = implode(', ', array_map(fn ($id) => $this->label($uniforms[$id] ?? null, $id), $uniformIds));
        }

        return $labels;
    }

    /**
     * Names only (uniform_name, else uniform_type), for forms such as the
     * KEW.PS-8 that print what the uniform is called.
     */
    public function namesForOrder(int $orderId): string
    {
        $uniformIds = $this->uniformIdsByOrder([$orderId])[$orderId] ?? [];
        $uniforms = $this->uniforms($uniformIds);

        return implode(', ', array_filter(array_map(function ($id) use ($uniforms) {
            $uniform = $uniforms[$id] ?? null;
            if (!$uniform) {
                return '';
            }
            $name = trim((string) ($uniform->uniform_name ?? ''));

            return $name !== '' ? $name : trim((string) ($uniform->uniform_type ?? ''));
        }, $uniformIds)));
    }

    /**
     * Narrows an orders query to those holding the uniform: as their first
     * uniform, or on any line.
     *
     * @param  \Illuminate\Database\Query\Builder $query a query on `orders`
     */
    public function whereHasUniform($query, int $uniformId)
    {
        return $query->where(function ($q) use ($uniformId) {
            $q->where('orders.uniforms_id', '=', $uniformId)
                ->orWhereExists(function ($lines) use ($uniformId) {
                    $lines->select(DB::raw(1))
                        ->from('ordered_clothes')
                        ->whereColumn('ordered_clothes.order_id', 'orders.id')
                        ->whereRaw(self::LINE_UNIFORM_SQL . ' = ?', [$uniformId]);
                });
        });
    }

    /**
     * One entry per order per uniform it holds, each a copy of the order with
     * uniforms_id set to that uniform -- for reports written when an order
     * had a single uniform, which compare $order->uniforms_id.
     *
     * @param  iterable<object> $orders
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function expandByUniform(iterable $orders): \Illuminate\Support\Collection
    {
        $orders = collect($orders);
        $idsByOrder = $this->uniformIdsByOrder($orders->pluck('id')->all());

        return $orders->flatMap(function ($order) use ($idsByOrder) {
            $uniformIds = $idsByOrder[(int) $order->id] ?? [(int) $order->uniforms_id];

            return array_map(function ($uniformId) use ($order) {
                $copy = clone $order;
                $copy->uniforms_id = $uniformId;

                return $copy;
            }, $uniformIds);
        })->values();
    }

    /**
     * The order's lines belonging to one uniform.
     */
    public function linesForUniform(object $order, int $uniformId): \Illuminate\Support\Collection
    {
        return DB::table('ordered_clothes')
            ->join('orders', 'orders.id', '=', 'ordered_clothes.order_id')
            ->where('ordered_clothes.order_id', '=', $order->id)
            ->whereRaw(self::LINE_UNIFORM_SQL . ' = ?', [$uniformId])
            ->select('ordered_clothes.*')
            ->get();
    }

    /**
     * @param  array<int, int> $uniformIds
     * @return array<int, object> keyed by uniform id
     */
    public function uniforms(array $uniformIds): array
    {
        $uniformIds = array_values(array_unique(array_filter(array_map('intval', $uniformIds))));
        if (!$uniformIds) {
            return [];
        }

        return DB::table('uniforms')->whereIn('id', $uniformIds)->get()->keyBy('id')->all();
    }

    public function label(?object $uniform, int $fallbackId = 0): string
    {
        if (!$uniform) {
            return $fallbackId ? 'Uniform #' . $fallbackId : '';
        }

        return trim($uniform->uniform_type . ($uniform->uniform_name ? ' (' . $uniform->uniform_name . ')' : ''));
    }
}
