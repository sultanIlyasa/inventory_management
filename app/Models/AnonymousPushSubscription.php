<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class AnonymousPushSubscription extends Model
{
    protected $fillable = [
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
    ];

    /**
     * Send notification to this subscription
     */
    public function sendNotification(array $payload)
    {
        $auth = [
            'VAPID' => [
                'subject' => config('webpush.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);

        $subscription = Subscription::create([
            'endpoint' => $this->endpoint,
            'publicKey' => $this->public_key,
            'authToken' => $this->auth_token,
            'contentEncoding' => $this->content_encoding,
        ]);

        $webPush->queueNotification(
            $subscription,
            json_encode($payload)
        );

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if (!$report->isSuccess()) {
                // Delete invalid subscriptions
                if ($report->getResponse() && $report->getResponse()->getStatusCode() === 410) {
                    $this->delete();
                }
            }
        }
    }

    /**
     * Send to all subscriptions
     */
    public static function sendToAll(array $payload)
    {
        $subscriptions = self::all();

        foreach ($subscriptions as $subscription) {
            $subscription->sendNotification($payload);
        }
    }
}
