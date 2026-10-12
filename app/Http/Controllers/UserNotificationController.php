<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use DB;

	/**
	 * Backs the notification bell in the shared header for a member on the
	 * web app. The mobile app has its own copy of this
	 * (Api\NotificationController) because it authenticates differently
	 * (bearer token, not session) -- both read the same user_notifications
	 * table, so a member sees the same inbox either way.
	 */
	class UserNotificationController extends Controller
	{
		private const MAX_PER_PAGE = 50;

		public function index(Request $request) {

			$userId = (int) $request->session()->get('user_id', 0);
			if ($userId <= 0) {
				return response()->json(['notifications' => [], 'unread_count' => 0], 401);
			}

			if (!\Illuminate\Support\Facades\Schema::hasTable('user_notifications')) {
				return response()->json(['notifications' => [], 'unread_count' => 0]);
			}

			$limit = (int) $request->query('limit', 20);
			$limit = max(1, min($limit, self::MAX_PER_PAGE));

			$rows = DB::table('user_notifications')
				->where('gen_user_id', '=', $userId)
				->orderByDesc('id')
				->limit($limit)
				->get();

			return response()->json([
				'notifications' => $rows->map(fn ($row) => $this->present($row))->all(),
				'unread_count' => $this->unreadCount($userId),
			]);
		}

		public function markRead(Request $request) {

			$userId = (int) $request->session()->get('user_id', 0);
			if ($userId <= 0) {
				return response()->json(['unread_count' => 0], 401);
			}

			if (!\Illuminate\Support\Facades\Schema::hasTable('user_notifications')) {
				return response()->json(['unread_count' => 0]);
			}

			$query = DB::table('user_notifications')
				->where('gen_user_id', '=', $userId)
				->whereNull('read_at');

			$ids = $request->input('ids');
			if (is_array($ids) && !empty($ids)) {
				$ids = array_values(array_filter(array_map('intval', $ids)));
				if (empty($ids)) {
					return response()->json(['unread_count' => $this->unreadCount($userId)]);
				}
				$query->whereIn('id', $ids);
			}

			$query->update(['read_at' => now(), 'updated_at' => now()]);

			return response()->json(['unread_count' => $this->unreadCount($userId)]);
		}

		private function unreadCount(int $userId): int {
			return DB::table('user_notifications')
				->where('gen_user_id', '=', $userId)
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
