<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {--email= : Admin email address}
                            {--password= : Admin password}
                            {--first_name= : First name}
                            {--last_name= : Last name}
                            {--superadmin : Assign Superadmin group instead of Admin}';

    protected $description = 'Create a new admin user';

    public function handle(): int
    {
        $email      = $this->option('email')      ?? $this->ask('Email');
        $password   = $this->option('password')   ?? $this->secret('Password');
        $firstName  = $this->option('first_name') ?? $this->ask('First name', 'Admin');
        $lastName   = $this->option('last_name')  ?? $this->ask('Last name', 'User');
        $isSuperadmin = $this->option('superadmin');

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email [{$email}] already exists.");
            return self::FAILURE;
        }

        $groupName = $isSuperadmin ? 'Superadmin' : 'Admin';
        $group = UserGroup::where('name', $groupName)->first();

        if (! $group) {
            $this->error("UserGroup [{$groupName}] not found. Run db:seed --class=LoginSeeder first.");
            return self::FAILURE;
        }

        $user = User::create([
            'first_name'        => $firstName,
            'last_name'         => $lastName,
            'email'             => $email,
            'password'          => Hash::make($password),
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        $user->userGroups()->attach($group->id);

        $this->info("Admin user created successfully.");
        $this->table(['Field', 'Value'], [
            ['Email',   $email],
            ['Group',   $groupName],
            ['Status',  'active'],
        ]);

        return self::SUCCESS;
    }
}
