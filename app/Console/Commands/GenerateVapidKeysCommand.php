<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:vapid';
    protected $description = 'Generate the VAPID key pair used for phone/browser push notifications';

    public function handle(): int
    {
        if (config('services.webpush.public_key')) {
            $this->warn('VAPID keys are already set. Replacing them signs out every device from notifications; employees would have to enable them again.');
            if (! $this->confirm('Generate new keys anyway?')) {
                return self::SUCCESS;
            }
        }

        $keys = VAPID::createVapidKeys();

        $this->info('Add these lines to your .env file, then run: php artisan config:clear');
        $this->newLine();
        $this->line("VAPID_PUBLIC_KEY={$keys['publicKey']}");
        $this->line("VAPID_PRIVATE_KEY={$keys['privateKey']}");
        $this->newLine();
        $this->comment('Local and Hostinger can each have their own keys, but once the live site has keys, keep them.');

        return self::SUCCESS;
    }
}
