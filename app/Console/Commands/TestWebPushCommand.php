<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Console\Command;

/**
 * Diagnose phone notifications: checks the setup, sends a test push and
 * prints what the push service (Google / Apple / Mozilla) answered per device.
 */
class TestWebPushCommand extends Command
{
    protected $signature = 'webpush:test {employee_code? : Only this user\'s devices (default: all devices)}';
    protected $description = 'Send a test push notification and show the result for each device';

    public function handle(): int
    {
        $this->line('<info>1. Setup</info>');
        $public  = (string) config('services.webpush.public_key');
        $subject = (string) config('services.webpush.subject');
        $this->line('   VAPID public key : ' . ($public ? substr($public, 0, 12) . '…' : '<error>MISSING</error>'));
        $this->line('   VAPID private key: ' . (config('services.webpush.private_key') ? 'set' : '<error>MISSING</error>'));
        $this->line("   VAPID subject    : {$subject}" . (preg_match('~^(https://|mailto:)~', $subject) ? '' : '  <comment>(should be https://… or mailto:…)</comment>'));
        $this->line('   APP_URL          : ' . config('app.url'));

        if (! WebPushChannel::isConfigured()) {
            $this->error('VAPID keys are not loaded. Add them to .env and run: php artisan config:clear');
            return self::FAILURE;
        }

        $devices = PushSubscription::with('user')
            ->when($this->argument('employee_code'), fn ($q, $code) => $q->whereHas('user', fn ($u) => $u->where('employee_code', $code)))
            ->get();

        $this->newLine();
        $this->line("<info>2. Devices</info> ({$devices->count()})");
        if ($devices->isEmpty()) {
            $this->warn('   No devices registered. On the phone: open the portal → bell → Turn on phone notifications → Allow.');
            return self::FAILURE;
        }

        foreach ($devices as $d) {
            $service = parse_url($d->endpoint, PHP_URL_HOST);
            $this->line("   #{$d->id}  {$d->user?->name} ({$d->user?->employee_code})  via {$service}  — registered {$d->updated_at->diffForHumans()}");
        }

        $this->newLine();
        $this->line('<info>3. Sending test…</info>');
        $results = WebPushChannel::deliver($devices, [
            'title' => 'Test notification',
            'body'  => 'Sent from the server at ' . now()->format('h:i:s A') . '. If you see this, phone notifications work.',
            'url'   => url('/login'),
            'icon'  => '/images/icon-192.png',
            'badge' => '/images/icon-192.png',
        ]);

        $failed = 0;
        foreach ($results as $r) {
            if ($r['ok']) {
                $this->line("   #{$r['id']}  <info>ACCEPTED</info> ({$r['status']}) — the push service will deliver it to the phone.");
            } else {
                $failed++;
                $this->line("   #{$r['id']}  <error>FAILED</error> ({$r['status']}) " . ($r['expired'] ? '— device unsubscribed; removed. Turn notifications on again on that phone.' : "— {$r['reason']}"));
            }
        }

        $this->newLine();
        if ($failed === 0) {
            $this->info('Every device was accepted. If a phone still shows nothing, the block is on the phone: notification permission, battery saver, or Do Not Disturb.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
