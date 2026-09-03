<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class InviteAdmin extends Command
{
    protected $signature = 'admin:invite {name : Full name} {email : Email address} {--hours=48 : How long the link stays valid}';

    protected $description = 'Create an admin account and print a one-time link so they can set their own password';

    public function handle(): int
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $hours = (int) $this->option('hours');

        if (User::where('email', $email)->exists()) {
            $this->error("A user with {$email} already exists. Promote or reset that account manually instead of using this command.");

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'is_admin' => true,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make(Str::random(64)),
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute('admin.setup', now()->addHours($hours), ['user' => $user]);

        $this->info("Admin invite created for {$name} <{$email}>.");
        $this->line("Share this link, it stays valid for {$hours} hours until the password is set:");
        $this->newLine();
        $this->line($url);
        $this->newLine();
        $this->comment('The link stops working as soon as the password is set.');

        return self::SUCCESS;
    }
}
