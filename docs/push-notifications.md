# Push notifications (order updates → plas-mobile)

When an admin approves, rejects, or edits the remarks / collection date of a
uniform order, the member is told. Two channels carry it:

| Channel | Works when | Configured by |
| --- | --- | --- |
| In-app inbox (`GET /api/notifications`) | Always | Nothing — it works today |
| Background push (FCM) | Firebase credentials present | The `.env` keys below |

The inbox is the source of truth. Every notification is recorded in
`user_notifications` **before** push is attempted, so a missed, failed, or
unconfigured push never loses a message — the member sees it next time they
open the app. That is why the feature is complete and usable before Firebase
exists.

## What triggers a notification

All four come from one place: `AdminController::updateUniformOrderStatus`,
which saves status, remarks and collection date in a single submit.
`OrderNotificationService` compares the order before and after and emits only
what actually changed — re-saving the form untouched notifies nobody.

| Change | Type |
| --- | --- |
| Status → approved | `order_approved` |
| Status → rejected | `order_rejected` |
| Remarks edited | `order_remarks_updated` |
| Collection date set/changed/cleared | `order_collection_date_updated` |

Two deliberate exceptions to "one change, one message":

- An **approval that also sets the collection date** is one message
  ("diluluskan… Tarikh kutipan: 15/09/2026"), not two.
- A **rejection with remarks** folds the remarks in as the reason, not two.

## Enabling background push

Nothing in the code changes. You need a Firebase project, then:

**1. Backend (plas-vv)** — add to `.env`:

```
FCM_PROJECT_ID=your-firebase-project-id
FCM_CREDENTIALS=C:\secure\path\firebase-service-account.json
```

Download the service-account JSON from Firebase Console → Project settings →
Service accounts → Generate new private key. **Store it outside `public/`**
and outside the repo. `App\Services\FcmSender` reads it, mints an OAuth2
token, and posts to FCM HTTP v1.

Until both keys are set, `FcmSender::isConfigured()` is false and push is
skipped silently — no errors, no crashes, inbox unaffected.

**2. Mobile (plas-mobile)**:

```
flutterfire configure          # writes lib/firebase_options.dart
```

then drop the generated files in place:

- `android/app/google-services.json`
- `ios/Runner/GoogleService-Info.plist`

`PushService.initialise()` already calls `Firebase.initializeApp()` inside a
try/catch, so the app runs normally without these and starts delivering push
once they land. The Android notification channel (`plas_orders`) and the
`POST_NOTIFICATIONS` permission are already declared in the manifest, and
`FcmSender` targets that same channel id.

## API

All require the mobile bearer token.

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/api/notifications` | Inbox + `unread_count`. `?limit=` (1–100, default 50) |
| `POST` | `/api/notifications/read` | Mark read. Body `{"ids": [...]}` or empty for all |
| `POST` | `/api/devices` | Register FCM token: `{"token": "...", "platform": "android\|ios"}` |
| `DELETE` | `/api/devices` | Unregister: `{"token": "..."}` |

`POST /api/auth/logout` also accepts `{"device_token": "..."}` so signing out
detaches the handset in one request.

`GET /api/notifications` response:

```json
{
  "notifications": [
    {
      "id": 12,
      "type": "order_approved",
      "title": "Pesanan Diluluskan",
      "body": "Pesanan uniform #99 anda telah diluluskan. Tarikh kutipan: 15/09/2026.",
      "order_id": 99,
      "read": false,
      "created_at": "2026-08-17 20:14:03"
    }
  ],
  "unread_count": 1
}
```

## Device token ownership

`device_tokens.token` is unique on its own, not per user. FCM hands a token to
whichever install registered it last, so registering an existing token
**moves** the row to the current user rather than adding a second one —
otherwise a shared handset would keep receiving the previous member's order
updates. Tokens FCM reports as dead (HTTP 403/404) are pruned automatically
after a send.

## Storage

| Table | Migration |
| --- | --- |
| `user_notifications` | `2026_08_17_000001_create_user_notifications_table.php` |
| `device_tokens` | `2026_08_17_000002_create_device_tokens_table.php` |

Both are guarded by `Schema::hasTable` checks at runtime, so the app degrades
to "no notifications" rather than erroring if migrations have not been run.
