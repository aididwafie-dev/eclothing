<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use DB;

	/**
	 * Backs the notification bell in the shared admin header. Session-based,
	 * like the rest of the admin area -- the mobile API's bearer-token
	 * NotificationController is a separate, unrelated path for members.
	 */
	class AdminNotificationController extends Controller
	{
		private const MAX_PER_PAGE = 50;

		public function index(Request $request) {

			$adminId = (int) $request->session()->get('admin_id', 0);
			if ($adminId <= 0) {
				return response()->json(['notifications' => [], 'unread_count' => 0], 401);
			}

			if (!\Illuminate\Support\Facades\Schema::hasTable('admin_notifications')) {
				return response()->json(['notifications' => [], 'unread_count' => 0]);
			}

			$limit = (int) $request->query('limit', 20);
			$limit = max(1, min($limit, self::MAX_PER_PAGE));

			$rows = DB::table('admin_notifications')
				->where('admin_id', '=', $adminId)
				->orderByDesc('id')
				->limit($limit)
				->get();

			return response()->json([
				'notifications' => $rows->map(fn ($row) => $this->present($row))->all(),
				'unread_count' => $this->unreadCount($adminId),
			]);
		}

		public function markRead(Request $request) {

			$adminId = (int) $request->session()->get('admin_id', 0);
			if ($adminId <= 0) {
				return response()->json(['unread_count' => 0], 401);
			}

			if (!\Illuminate\Support\Facades\Schema::hasTable('admin_notifications')) {
				return response()->json(['unread_count' => 0]);
			}

			$query = DB::table('admin_notifications')
				->where('admin_id', '=', $adminId)
				->whereNull('read_at');

			$ids = $request->input('ids');
			if (is_array($ids) && !empty($ids)) {
				$ids = array_values(array_filter(array_map('intval', $ids)));
				if (empty($ids)) {
					return response()->json(['unread_count' => $this->unreadCount($adminId)]);
				}
				$query->whereIn('id', $ids);
			}

			$query->update(['read_at' => now(), 'updated_at' => now()]);

			return response()->json(['unread_count' => $this->unreadCount($adminId)]);
		}

		private function unreadCount(int $adminId): int {
			return DB::table('admin_notifications')
				->where('admin_id', '=', $adminId)
				->whereNull('read_at')
				->count();
		}

		private function present($row): array {
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
