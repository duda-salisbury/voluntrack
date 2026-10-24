<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('createAdmin {--name= : Administrator name} {--email= : Administrator email}', function () {
    $interactive = $this->input->isInteractive();
    $name = $this->option('name');
    $email = $this->option('email');

    if (! $interactive && (blank($name) || blank($email))) {
        $this->error('Non-interactive execution requires both --name and --email. A password-setup link will be emailed; no password argument is needed.');

        return Command::FAILURE;
    }

    $name = filled($name) ? $name : $this->ask('Name');
    $email = filled($email) ? $email : $this->ask('Email');
    $password = $interactive ? $this->secret('Password (at least 8 characters)') : Str::random(64);
    $confirmation = $interactive ? $this->secret('Confirm password') : $password;

    $validator = Validator::make([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $confirmation,
    ], [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }

        return Command::FAILURE;
    }

    User::create([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'is_admin' => true,
        'organization_id' => null,
    ]);

    if ($interactive) {
        $this->info("Platform administrator created: {$email}. Sign in at /login.");

        return Command::SUCCESS;
    }

    try {
        $status = Password::sendResetLink(['email' => $email]);
    } catch (TransportExceptionInterface $exception) {
        report($exception);
        $this->error('The platform administrator was created, but the password-setup email could not be sent. Check mail configuration, then request a fresh link at /account/password/forgot. Do not recreate the account.');

        return Command::FAILURE;
    }

    if ($status !== Password::RESET_LINK_SENT) {
        $this->error('The platform administrator was created, but the password-setup link could not be sent: '.__($status));
        $this->error('Request a fresh link at /account/password/forgot. Do not recreate the account.');

        return Command::FAILURE;
    }

    $this->info("Platform administrator created: {$email}. Password-setup email sent through the configured mailer. Set your password, then sign in at /login.");

    return Command::SUCCESS;
})->purpose('Create a platform administrator interactively or email a password-setup link with --no-interaction');
