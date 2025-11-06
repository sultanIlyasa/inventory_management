<?php

namespace App\Http\Controllers;

use App\Models\AnonymousPushSubscription;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    /**
     * Subscribe (no auth required!)
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        AnonymousPushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => 'aes128gcm',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to notifications'
        ]);
    }

    /**
     * Unsubscribe (no auth required!)
     */
    public function unsubscribe(Request $request)
    {
        $endpoint = $request->input('endpoint');

        AnonymousPushSubscription::where('endpoint', $endpoint)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully unsubscribed'
        ]);
    }

    /**
     * Test notification (for debugging)
     */


    public function testNotification(Request $request)
    {
        // Add some logging to debug

        $payload = [
            'title' => 'Test Notification',
            'body' => 'This is a test message from your Material Management System',
            'icon' => '/pwa-192x192.png',
            'badge' => '/badge-icon.png',
            'tag' => 'test-notification',
            'data' => [
                'url' => url('/dashboard'),
                'timestamp' => now()->toIso8601String(),
            ],
        ];

        try {
            $count = AnonymousPushSubscription::count();

            if ($count === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subscribers found. Please subscribe first.',
                ], 404);
            }

            AnonymousPushSubscription::sendToAll($payload);

            return response()->json([
                'success' => true,
                'message' => "Test notification sent to {$count} subscriber(s)",
                'subscribers' => $count,
            ]);
        } catch (\Exception $e) {


            return response()->json([
                'success' => false,
                'message' => 'Error sending notification: ' . $e->getMessage()
            ], 500);
        }
    }
}
