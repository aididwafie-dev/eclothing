<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * The email-based Announcements broadcast, extracted out of
 * AdminController::sendAnnouncement so the Blade page and the
 * Api\Admin\AnnouncementController JSON endpoint share one copy of the
 * audience rules. Distinct from AdminNotificationService/the System
 * Settings push-announcement feature, which is in-app/push, not email.
 */
class AdminAnnouncementService
{
    public const AUDIENCES = ['all', 'unconfirmed', 'confirmed', 'no_orders'];

    public function validate(array $input): \Illuminate\Validation\Validator
    {
        return Validator::make($input, [
            'from' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'send_to' => ['required', 'in:' . implode(',', self::AUDIENCES)],
        ]);
    }

    /** How many active members a given audience choice currently reaches. */
    public function audienceCount(string $sendTo): int
    {
        return $this->audienceQuery($sendTo)->count();
    }

    /**
     * Sends the broadcast and returns how many of the audience it actually
     * reached. A bad address or SMTP hiccup for one recipient does not stop
     * the rest -- same best-effort semantics as the System Settings push
     * broadcast.
     */
    public function send(string $from, string $subject, string $body, string $sendTo): int
    {
        $recipients = $this->audienceQuery($sendTo)->get();

        $sentTo = 0;
        foreach ($recipients as $recipient) {
            if (!$recipient->email) {
                continue;
            }
            try {
                Mail::raw($body, function ($message) use ($subject, $from, $recipient) {
                    $message->from($from, 'e-Clothing');
                    $message->to($recipient->email)->subject($subject);
                });
                $sentTo++;
            } catch (\Throwable $e) {
                // Best-effort: one bad address must not abort the batch.
            }
        }

        return $sentTo;
    }

    /**
     * 'no_orders' used to be an N+1 loop (one `orders` lookup per active
     * member); a single NOT EXISTS query reaches the same members. Also
     * now scopes to non-deleted orders, matching every other
     * "has this user ordered" check in the app (All Users' filter dropdown,
     * most notably) -- the original loop didn't, which read as an
     * inconsistency rather than a deliberate rule.
     */
    private function audienceQuery(string $sendTo)
    {
        $query = DB::table('gen_users')->where('status', '=', 1);

        return match ($sendTo) {
            'unconfirmed' => $query->where('activation_status', '=', 0),
            'confirmed' => $query->where('activation_status', '=', 1),
            'no_orders' => $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('orders')
                    ->whereColumn('orders.user_id', 'gen_users.id')
                    ->where('orders.deleted', '=', 0);
            }),
            default => $query,
        };
    }
}
