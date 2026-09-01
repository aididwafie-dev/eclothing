<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderStatusService;
use Barryvdh\DomPDF\Facade\Pdf;
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
    use \App\Http\Controllers\Concerns\BuildsKewPs8Report;

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
                // Lets the mobile app reopen the Order Uniform tab on the
                // right uniform when a member edits a pending order.
                'uniformsId' => (string) $order->uniforms_id,
                'uniformType' => $uniform->uniform_type ?? '',
                'uniformName' => $uniform->uniform_name ?? null,
                'itemCount' => $items->count(),
                'status' => $order->status_key,
                'statusLabel' => $order->status_label,
                // Authoritative answer to "can this still be changed?", so the
                // client never has to re-derive the rule from the status key.
                'editable' => app(OrderStatusService::class)->isOrderEditable($order->status ?? null),
                'uniformPhotoUrl' => AssetController::urlFor($uniform->uniform_photo ?? null),
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
        $statusSvc = app(OrderStatusService::class);

        $hasProcessing = false;
        if ($statusSvc->hasOrderLifecycleColumns()) {
            $userOrders = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $genUser->id)->get();
            foreach ($userOrders as $o) {
                $meta = $statusSvc->orderStatusMeta($o->status ?? null);
                if ($meta['key'] === 'processing') {
                    $hasProcessing = true;
                    break;
                }
            }
        }
        if ($hasProcessing) {
            return response()->json([
                'message' => 'One or more orders are currently being processed and cannot be deleted. Please contact the administrator.',
            ], 403);
        }

        DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $genUser->id)->update(['deleted' => 1]);

        return response()->json(['message' => 'Your orders have been deleted.']);
    }

    /**
     * Printable KEW.PS-8 (Borang Permohonan Stok) for a single order, for the
     * mobile app. Mirrors DashboardController::generateKewPs8Report but resolves
     * the user from the mobile bearer token instead of the web session, and
     * renders the same reports.kew_ps8 blade so the layout stays identical to
     * the web app. Ownership is enforced via the where('user_id', ...) clause.
     */
    public function kewPs8(Request $request, $id)
    {
        $genUser = $request->attributes->get('gen_user');

        $order = DB::table('orders')->where('id', '=', $id)->where('user_id', '=', $genUser->id)->where('deleted', '=', 0)->first();
        if (!$order) {
            abort(404);
        }

        $personalDetail = DB::table('personal_details')->where('user_id', '=', $genUser->id)->first();

        $uniform = DB::table('uniforms')->where('id', '=', $order->uniforms_id)->first();
        $items = DB::table('ordered_clothes')->where('order_id', '=', $order->id)->get();

        $pdf = Pdf::loadView('reports.kew_ps8', [
            'order' => $order,
            'uniform' => $uniform,
            'applicantName' => $this->kewPs8SignatoryName($personalDetail),
            'applicantPosition' => $this->kewPs8ApplicantPosition($genUser->id),
            'printedAt' => $this->kewPs8PrintedAt(),
            'orderReference' => $this->kewPs8OrderReference($order),
            'uniformName' => $this->kewPs8UniformName($uniform),
            'approver' => $this->kewPs8Approver($order),
            'reportForms' => $this->chunkKewPs8Rows($items),
            'forPdf' => true,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('KEW-PS8-' . $this->kewPs8OrderReference($order) . '.pdf');
    }
}
