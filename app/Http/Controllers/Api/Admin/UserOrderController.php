<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderStatusService;
use App\Services\OrderUniformService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON counterpart to AdminController's listUserUniformDetails /
 * fromEditUserUniformDetails / saveUniformEditedDetails / deleteOrder --
 * the "Uniform Details" of one member, opened from All Users.
 * Superadmin-only.
 */
class UserOrderController extends Controller
{
    public function __construct(private OrderStatusService $statuses, private OrderUniformService $orderUniforms)
    {
    }

    public function index(string $userId)
    {
        $orders = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', (int) $userId)->get();
        $labels = $this->orderUniforms->labelsByOrder($orders->pluck('id')->all());

        return response()->json([
            'orders' => $orders->map(function ($order) use ($labels) {
                $order = $this->statuses->normalizeOrderLifecycle($order);

                return [
                    'id' => (string) $order->id,
                    'uniform' => $labels[(int) $order->id] ?? $this->fallbackLabel($order),
                    'statusClass' => $order->status_class ?? 'status-pending',
                    'statusLabel' => $order->status_label ?? 'Pending',
                    'collectionDate' => $order->collection_date,
                    'remarks' => trim((string) $order->remarks),
                    'lines' => $this->lines((int) $order->id),
                ];
            })->values(),
        ]);
    }

    public function show(string $orderId)
    {
        $order = DB::table('orders')->where('deleted', '=', 0)->where('id', '=', (int) $orderId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json([
            'order' => [
                'id' => (string) $order->id,
                'userId' => (string) $order->user_id,
                'uniform' => $this->orderUniforms->labelsByOrder([$order->id])[(int) $order->id] ?? $this->fallbackLabel($order),
                'lines' => $this->lines((int) $order->id),
            ],
        ]);
    }

    /** saveUniformEditedDetails -- new sizes keyed by ordered_clothes id; FIX lines stay as they are. */
    public function update(Request $request, string $orderId)
    {
        $sizes = (array) $request->input('sizes', []);
        $lines = DB::table('ordered_clothes')->where('order_id', '=', (int) $orderId)->get();

        foreach ($lines as $line) {
            $size = strtoupper(trim((string) ($sizes[$line->id] ?? '')));
            if ($line->size === 'FIX' || $size === '' || $size === $line->size) {
                continue;
            }
            DB::table('ordered_clothes')->where('id', '=', $line->id)->update(['size' => $size, 'updated_at' => now()]);
        }

        return response()->json(['message' => 'Saved.']);
    }

    /** deleteOrder. */
    public function destroy(string $orderId)
    {
        DB::table('orders')->where('deleted', '=', 0)->where('id', '=', (int) $orderId)->delete();
        DB::table('ordered_clothes')->where('order_id', '=', (int) $orderId)->delete();

        return response()->json(['message' => 'Order removed.']);
    }

    private function lines(int $orderId): array
    {
        return DB::table('ordered_clothes')->where('order_id', '=', $orderId)->get()
            ->map(fn ($c) => ['id' => (string) $c->id, 'clothes' => $c->clothes, 'size' => $c->size])
            ->values()->all();
    }

    private function fallbackLabel(object $order): string
    {
        $uniform = DB::table('uniforms')->where('id', '=', $order->uniforms_id)->first();
        return $uniform ? $uniform->uniform_type . ($uniform->uniform_name ? ' (' . $uniform->uniform_name . ')' : '') : '-';
    }
}
