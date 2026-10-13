<?php

namespace App\Notifications\Channels;

use App\Models\PushSubscription;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Sends a notification to every browser/phone the user enabled notifications on.
 *
 * The notification provides toWebPush($notifiable): array{title, body, url?, tag?};
 * without it, toArray()['message'] is used as the body.
 */
class WebPushChannel
{
    public static function isConfigured(): bool
    {
        return (bool) config('services.webpush.public_key') && (bool) config('services.webpush.private_key');
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! self::isConfigured() || ! method_exists($notifiable, 'pushSubscriptions')) {
            return;
        }

        $subscriptions = $notifiable->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = method_exists($notification, 'toWebPush')
            ? $notification->toWebPush($notifiable)
            : ['title' => config('app.name'), 'body' => $notification->toArray($notifiable)['message'] ?? ''];

        $payload += ['url' => url(method_exists($notifiable, 'dashboardRoute') ? $notifiable->dashboardRoute() : '/'), 'icon' => '/images/icon-192.png', 'badge' => '/images/badge-96.png'];

        // Unread count for the number on the app icon. The database channel runs
        // first (see via()), so this already includes the notification being sent.
        if (method_exists($notifiable, 'unreadNotifications')) {
            $payload['unread'] = $notifiable->unreadNotifications()->count();
        }

        try {
            foreach (self::deliver($subscriptions, $payload) as $result) {
                if (! $result['ok'] && ! $result['expired']) {
                    Log::warning('Web push failed', $result);
                }
            }
        } catch (\Throwable $e) {
            // A push problem must never break the action that triggered it (approval, payroll, …).
            Log::error('Web push exception', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send $payload to each subscription and report what the push service answered.
     * Expired subscriptions (app uninstalled / permission revoked) are deleted.
     *
     * @param  iterable<PushSubscription>  $subscriptions
     * @return array<int, array{id: int, ok: bool, expired: bool, status: ?int, reason: string}>
     */
    public static function deliver(iterable $subscriptions, array $payload): array
    {
        $webPush = new WebPush(['VAPID' => [
            'subject'    => config('services.webpush.subject'),
            'publicKey'  => config('services.webpush.public_key'),
            'privateKey' => config('services.webpush.private_key'),
        ]], ['TTL' => 86400, 'urgency' => 'high']);

        $byEndpoint = [];
        foreach ($subscriptions as $sub) {
            $byEndpoint[$sub->endpoint] = $sub;
            $webPush->queueNotification(Subscription::create([
                'endpoint'        => $sub->endpoint,
                'publicKey'       => $sub->public_key,
                'authToken'       => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding,
            ]), json_encode($payload));
        }

        $results = [];
        foreach ($webPush->flush() as $report) {
            $sub     = $byEndpoint[$report->getEndpoint()] ?? null;
            $expired = $report->isSubscriptionExpired();

            if ($expired) {
                PushSubscription::where('endpoint_hash', hash('sha256', $report->getEndpoint()))->delete();
            }

            $results[] = [
                'id'      => $sub?->id ?? 0,
                'ok'      => $report->isSuccess(),
                'expired' => $expired,
                'status'  => $report->getResponse()?->getStatusCode(),
                'reason'  => $report->isSuccess() ? 'accepted' : mb_substr(trim($report->getReason() . ' ' . (string) $report->getResponse()?->getBody()), 0, 300),
            ];
        }

        return $results;
    }
}
