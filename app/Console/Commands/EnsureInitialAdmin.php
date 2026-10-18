<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class EnsureInitialAdmin extends Command
{
    protected $signature = 'campus:ensure-initial-admin';

    protected $description = 'Create the initial administrator when host environment variables are set';

    public function handle(): int
    {
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->info('No initial administrator is configured.');

            return self::SUCCESS;
        }

        $role = Role::query()->where('slug', Role::SLUG_ADMINISTRATOR)->first();

        if ($role === null) {
            $this->error('The administrator role has not been seeded.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = env('INITIAL_ADMIN_NAME', 'Campus Administrator');
        $user->role_id = $role->id;

        if (! $user->exists) {
            $user->password = $password;
        }

        $user->save();
        $this->info('Initial administrator is ready.');

        return self::SUCCESS;
    }
}
