<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SyncSpatieRolesSeeder extends Seeder
{
    public function run(): void
    {
        $synced = 0;

        User::all()->each(function (User $user) use (&$synced) {
            if ($user->role && ! $user->hasRole($user->role)) {
                $user->syncRoles($user->role);
                $synced++;
                $this->command->line("  Synced: {$user->name} → {$user->role}");
            }
        });

        $this->command->info("Done — {$synced} user(s) synced to Spatie roles.");
    }
}
