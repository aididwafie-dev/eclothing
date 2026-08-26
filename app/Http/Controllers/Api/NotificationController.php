<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The mobile app's notification inbox, and the device tokens push is
 * delivered to.
 *
 * The inbox is the durable channel: it is populated whether or not FCM is
 * configured, so a member who never receives a push still sees every
 * update the next time they open the app.
 */
class NotificationController extends Controller
{
    private const MAX_PER_PAGE = 100;

    public function index(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');
        $service = app(OrderNotificationService::class);

        if (!$service->storageReady()) {
            return response()->json(['notifications' => [], 'unread_count' => 0]);
        }

        $limit = (int) $request->query('limit', 50);
        $limit = max(1, min($limit, self::MAX_PER_PAGE));

        $rows = DB::table('user_notifications')
            ->where('gen_user_id', '=', $genUser->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $unreadCount = DB::table('user_notifications')
            ->where('gen_user_id', '=', $genUser->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $rows->map(fn ($row) => $this->present($row))->all(),
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Marks notifications read. Without an `ids` array every unread
     * notification for the user is marked, which is what opening the
     * inbox does.
     */
    public function markRead(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');
        $service = app(OrderNotificationService::class);

        if (!$service->storageReady()) {
            return response()->json(['unread_count' => 0]);
        }

        $query = DB::table('user_notifications')
            ->where('gen_user_id', '=', $genUser->id)
            ->whereNull('read_at');

        $ids = $request->input('ids');
        if (is_array($ids) && !empty($ids)) {
            $ids = array_values(array_filter(array_map('intval', $ids)));
            if (empty($ids)) {
                return response()->json(['unread_count' => $this->unreadCount($genUser->id)]);
            }
            $query->whereIn('id', $ids);
        }

        $query->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['unread_count' => $this->unreadCount($genUser->id)]);
    }

    /**
     * Registers this device's FCM token against the signed-in user.
     *
     * FCM hands a token to whichever install registered it last, so the row
     * is moved to the current user rather than duplicated -- otherwise a
     * shared handset would keep pushing the previous user's orders.
     */
    public function registerDevice(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');
        $service = app(OrderNotificationService::class);

        $token = trim((string) $request->input('token'));
        $platform = strtolower(trim((string) $request->input('platform')));

        if ($token === '' || strlen($token) > 255) {
            return response()->json(['message' => 'A device token is required.'], 422);
        }

        if (!in_array($platform, ['android', 'ios', ''], true)) {
            return response()->json(['message' => 'Unsupported platform.'], 422);
        }

        if (!$service->deviceStorageReady()) {
            return response()->json(['message' => 'Device registration is not available.'], 503);
        }

        DB::table('device_tokens')->updateOrInsert(
            ['token' => $token],
            [
                'gen_user_id' => $genUser->id,
                'platform' => $platform !== '' ? $platform : null,
                'last_seen_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['message' => 'Device registered.']);
    }

    /**
     * Drops a device token, so a signed-out handset stops receiving the
     * member's order updates.
     */
    public function unregisterDevice(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');
        $service = app(OrderNotificationService::class);

        $token = trim((string) $request->input('token'));

        if ($token === '') {
            return response()->json(['message' => 'A device token is required.'], 422);
        }

        if (!$service->deviceStorageReady()) {
            return response()->noContent();
        }

        // Scoped to the owner so one user cannot unregister another's device.
        DB::table('device_tokens')
            ->where('token', '=', $token)
            ->where('gen_user_id', '=', $genUser->id)
            ->delete();

        return response()->noContent();
    }

    private function unreadCount($userId): int
    {
        return DB::table('user_notifications')
            ->where('gen_user_id', '=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    private function present($row): array
    {
        return [
            'id' => (int) $row->id,
            'type' => (string) $row->type,
            'title' => (string) $row->title,
            'body' => (string) $row->body,
            'order_id' => $row->order_id !== null ? (int) $row->order_id : null,
            'read' => $row->read_at !== null,
            'created_at' => $row->created_at,
        ];
    }
}
