<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * JSON counterpart to DashboardController's
 * getOrderedUniform/mailUserOrderDetails/deleteUserOrder. The
 * email/delete actions operate on *all* of the user's orders at once,
 * matching the web app's toolbar (its AJAX calls carry no order id)
 * -- see API_CONTRACT.md.
 */
class OrderController extends Controller
{
    public function index(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');

        $orders = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $genUser->id)->get();

        $result = $orders->map(function ($order) {
            $order = app(OrderStatusService::class)->normalizeOrderLifecycle($order);
            $uniform = DB::table('uniforms')->where('id', '=', $order->uniforms_id)->first();
            $items = DB::table('ordered_clothes')->where('order_id', '=', $order->id)->get();

            return [
                'id' => (string) $order->id,
                'uniformType' => $uniform->uniform_type ?? '',
                'uniformName' => $uniform->uniform_name ?? null,
                'itemCount' => $items->count(),
                'status' => $order->status_key,
                'statusLabel' => $order->status_label,
                'uniformPhotoUrl' => $uniform && $uniform->uniform_photo ? asset('uploads/' . $uniform->uniform_photo) : null,
                'collectionDate' => $order->collection_date,
                'remarks' => $order->remarks,
                'updatedAt' => $order->updated_at,
                'items' => $items->map(fn ($i) => ['clothes' => $i->clothes, 'size' => $i->size])->values(),
            ];
        });

        return response()->json(['orders' => $result->values()]);
    }

    public function emailDetails(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');

        $orders = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $genUser->id)->get();

        $data = $orders->map(function ($order) {
            return [
                'userOrders' => $order,
                'orderedUniform' => DB::table('uniforms')->where('id', '=', $order->uniforms_id)->first(),
                'orderDetails' => DB::table('ordered_clothes')->where('order_id', '=', $order->id)->get(),
                'count' => DB::table('ordered_clothes')->where('order_id', '=', $order->id)->count(),
            ];
        })->values()->all();

        try {
            Mail::send('mail_user_orderDetails', ['data' => $data], function ($message) use ($genUser) {
                $message->subject('Order Summary from Personnel Logistic Accounting System');
                $message->from(config('mail.from.address'), config('mail.from.name'));
                $message->to($genUser->email);
            });
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Email could not be sent right now. Please contact the administrator.'], 500);
        }

        return response()->json(['message' => 'Order details have been emailed to you.']);
    }

    public function destroyAll(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');

        DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $genUser->id)->update(['deleted' => 1]);

        return response()->json(['message' => 'Your orders have been deleted.']);
    }
}
