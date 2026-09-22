<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResetAllPasswordsCommand extends Command
{
    protected $signature = 'users:reset-all-passwords';

    protected $description = 'Overwrite the password on every user account with one new password you provide (interactive)';

    public function handle(): int
    {
        $count = User::count();

        $this->warn("This will overwrite the password on all {$count} user account(s) and sign everyone out.");
        $this->line('Every user will need the new password to log in again — make sure you have a way to share it with them.');

        if (! $this->confirm('Are you sure you want to continue?')) {
            $this->line('Cancelled — no passwords were changed.');
            return self::SUCCESS;
        }

        $password = $this->secret('Enter the new password');
        $confirmation = $this->secret('Confirm the new password');

        if ($password !== $confirmation) {
            $this->error('Passwords did not match. No changes were made.');
            return self::FAILURE;
        }

        $validator = Validator::make(['password' => $password], ['password' => 'required|string|min:8']);
        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));
            return self::FAILURE;
        }

        User::query()->update([
            'password'       => Hash::make($password),
            'remember_token' => null,
        ]);

        ActivityLogService::log('bulk_password_reset', "Reset the password on all {$count} user account(s).");

        $this->info("Done — password updated on {$count} account(s). Remembered sessions were also cleared.");

        return self::SUCCESS;
    }
}
