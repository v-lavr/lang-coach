<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:create-development-token {email=langcoach@example.test} {--name=macos-development}')]
#[Description('Create a local user and Sanctum token for LangCoach development')]
class CreateDevelopmentToken extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::firstOrCreate(
            ['email' => (string) $this->argument('email')],
            ['name' => 'LangCoach Development', 'password' => 'development-only-password'],
        );

        $this->line($user->createToken((string) $this->option('name'))->plainTextToken);
        $this->newLine();
        $this->warn('Store this token securely. It is shown only once and must not be committed.');
        $this->line('TODO: Replace this manually provisioned token flow with Google OAuth (Authorization Code + PKCE), Keychain storage, and token lifecycle management before production.');

        return self::SUCCESS;
    }
}
