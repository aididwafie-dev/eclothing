<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAnnouncementService;
use Illuminate\Http\Request;

/**
 * JSON counterpart to AdminController::sendAnnouncement, for the admin
 * side of the Next.js UI. Superadmin-only.
 */
class AnnouncementController extends Controller
{
    public function __construct(private AdminAnnouncementService $announcements)
    {
    }

    /** Audience sizes for each send_to choice, shown before the admin sends. */
    public function audienceCounts()
    {
        $counts = [];
        foreach (AdminAnnouncementService::AUDIENCES as $audience) {
            $counts[$audience] = $this->announcements->audienceCount($audience);
        }

        return response()->json(['audienceCounts' => $counts]);
    }

    public function send(Request $request)
    {
        $validator = $this->announcements->validate($request->all());
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $sentTo = $this->announcements->send($validated['from'], $validated['subject'], $validated['body'], $validated['send_to']);

        return response()->json(['message' => 'Email sent to ' . $sentTo . ' users.', 'sentTo' => $sentTo]);
    }
}
