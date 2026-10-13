<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stores the push subscription a browser/phone creates when the employee
 * allows notifications (see resources/views/layouts/_push-notifications.blade.php).
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'         => 'required|url|max:2000',
            'keys.p256dh'      => 'required|string|max:255',
            'keys.auth'        => 'required|string|max:255',
            'contentEncoding'  => 'nullable|string|in:aes128gcm,aesgcm',
        ]);

        // A device belongs to whoever is logged in on it now.
        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'user_id'          => $request->user()->id,
                'endpoint'         => $data['endpoint'],
                'public_key'       => $data['keys']['p256dh'],
                'auth_token'       => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'user_agent'       => mb_substr((string) $request->userAgent(), 0, 255),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|string']);

        $request->user()->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $request->input('endpoint')))
            ->delete();

        return response()->json(['ok' => true]);
    }
}
