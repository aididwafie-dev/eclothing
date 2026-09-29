<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Checks the Firebase push setup and, given a member's Service ID, sends
 * them a test notification on every device they have signed in on.
 *
 *   php artisan fcm:test
 *   php artisan fcm:test 733212
 */
Artisan::command('fcm:test {serviceId? : Service ID of a member to push a test notification to}', function (?string $serviceId = null) {
    $sender = app(\App\Services\FcmSender::class);

    $allOk = true;
    foreach ($sender->diagnose() as $check) {
        $allOk = $allOk && $check['ok'];
        $this->line(($check['ok'] ? '<info>  OK </info> ' : '<error> FAIL </error> ') . $check['check'] . ' - ' . $check['detail']);
    }
    if (!$allOk) {
        $this->warn('Push is off until the step above is fixed. See docs/push-notifications.md.');
        return 1;
    }
    $this->info('FCM is configured.');

    if ($serviceId === null) {
        $this->line('Pass a member\'s Service ID to send them a test notification.');
        return 0;
    }

    $member = \Illuminate\Support\Facades\DB::table('gen_users')->where('s_id', '=', $serviceId)->first();
    if (!$member) {
        $this->error("No member with Service ID {$serviceId}.");
        return 1;
    }

    $tokens = \Illuminate\Support\Facades\DB::table('device_tokens')->where('gen_user_id', '=', $member->id)->pluck('token')->all();
    if (!$tokens) {
        $this->warn("Member {$serviceId} has no registered device. Sign in to the mobile app on a phone first.");
        return 1;
    }

    $dead = $sender->send($tokens, 'e-Clothing', 'Test notification - push is working.', ['type' => 'test']);
    $this->info('Sent to ' . (count($tokens) - count($dead)) . ' of ' . count($tokens) . ' device(s).');
    if ($dead) {
        $this->warn(count($dead) . ' device token(s) are no longer valid (app uninstalled or signed out).');
    }

    return 0;
})->purpose('Check the Firebase push setup, optionally sending a member a test notification');
