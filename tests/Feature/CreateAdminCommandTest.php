<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(string $email = 'admin@example.test', string $password = 'secure-password-123', ?string $confirmation = null)
    {
        return $this->artisan('createAdmin')
            ->expectsQuestion('Name', 'Platform Administrator')
            ->expectsQuestion('Email', $email)
            ->expectsQuestion('Password (at least 8 characters)', $password)
            ->expectsQuestion('Confirm password', $confirmation ?? $password);
    }

    public function test_it_creates_a_platform_administrator_with_a_hashed_password(): void
    {
        $this->createAdmin()
            ->expectsOutput('Platform administrator created: admin@example.test. Sign in at /login.')
            ->assertSuccessful();

        $user = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame('Platform Administrator', $user->name);
        $this->assertTrue($user->isSuperAdmin());
        $this->assertNull($user->organization_id);
        $this->assertTrue(Hash::check('secure-password-123', $user->password));
    }

    public function test_it_rejects_invalid_email_and_short_password(): void
    {
        $this->createAdmin('not-an-email', 'short')->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_rejects_mismatched_password_confirmation(): void
    {
        $this->createAdmin(confirmation: 'a-different-password')->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_does_not_overwrite_or_promote_an_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.test', 'is_admin' => false]);
        $originalPassword = $user->password;

        $this->createAdmin()->assertFailed();

        $this->assertDatabaseCount('users', 1);
        $this->assertFalse($user->fresh()->is_admin);
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_it_does_not_reuse_a_soft_deleted_accounts_email(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.test']);
        $user->delete();

        $this->createAdmin()->assertFailed();

        $this->assertSoftDeleted($user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_interactive_options_skip_identity_prompts_but_keep_hidden_password_prompts(): void
    {
        Notification::fake();
        $this->artisan('createAdmin', ['--name' => 'Platform Admin', '--email' => 'admin@example.test'])
            ->expectsQuestion('Password (at least 8 characters)', 'secure-password-123')
            ->expectsQuestion('Confirm password', 'secure-password-123')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('secure-password-123', User::firstOrFail()->password));
        Notification::assertNothingSent();
    }

    public function test_non_interactive_creation_emails_a_valid_password_setup_token_without_prompts(): void
    {
        Notification::fake();
        $this->artisan('createAdmin', [
            '--name' => 'Platform Admin', '--email' => 'admin@example.test', '--no-interaction' => true,
        ])->assertSuccessful();

        $user = User::firstOrFail();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertNull($user->organization_id);
        $this->assertSame('Platform Admin', $user->name);
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->assertTrue(Password::tokenExists($user, $notification->token));
            $this->assertStringContainsString('/account/password/reset/', $notification->toMail($user)->actionUrl);

            return true;
        });
    }

    public function test_non_interactive_missing_options_fail_without_creating_accounts(): void
    {
        Notification::fake();
        foreach ([[], ['--name' => 'Platform Admin'], ['--email' => 'admin@example.test'], ['--name' => ' ', '--email' => 'admin@example.test']] as $options) {
            $this->artisan('createAdmin', [...$options, '--no-interaction' => true])
                ->expectsOutput('Non-interactive execution requires both --name and --email. A password-setup link will be emailed; no password argument is needed.')
                ->assertFailed();
        }
        $this->assertDatabaseCount('users', 0);
        Notification::assertNothingSent();
    }

    public function test_non_interactive_invalid_values_fail_validation(): void
    {
        Notification::fake();
        $this->artisan('createAdmin', [
            '--name' => 'Platform Admin', '--email' => 'not-an-email', '--no-interaction' => true,
        ])->assertFailed();
        $this->assertDatabaseCount('users', 0);
        Notification::assertNothingSent();
    }

    public function test_non_interactive_creation_never_changes_existing_accounts_including_archived_accounts(): void
    {
        Notification::fake();
        foreach ([false, true] as $isAdmin) {
            $user = User::factory()->create(['is_admin' => $isAdmin]);
            $original = $user->refresh()->getAttributes();
            $this->artisan('createAdmin', [
                '--name' => 'Replacement', '--email' => $user->email, '--no-interaction' => true,
            ])->assertFailed();
            $this->assertSame($original, $user->fresh()->getAttributes());
            $user->delete();
            $this->artisan('createAdmin', [
                '--name' => 'Replacement', '--email' => $user->email, '--no-interaction' => true,
            ])->assertFailed();
            $this->assertSoftDeleted($user);
        }
        $this->assertDatabaseCount('users', 2);
        Notification::assertNothingSent();
    }

    public function test_mail_failure_reports_failure_and_explains_recovery_without_deleting_the_account(): void
    {
        Password::shouldReceive('sendResetLink')->once()->with(['email' => 'admin@example.test'])
            ->andThrow(new TransportException('Mail service unavailable'));

        $this->artisan('createAdmin', [
            '--name' => 'Platform Admin', '--email' => 'admin@example.test', '--no-interaction' => true,
        ])->expectsOutput('The platform administrator was created, but the password-setup email could not be sent. Check mail configuration, then request a fresh link at /account/password/forgot. Do not recreate the account.')
            ->assertFailed();
        $this->assertTrue(User::firstOrFail()->isSuperAdmin());
    }

    public function test_password_broker_failure_returns_nonzero_instead_of_claiming_email_was_sent(): void
    {
        Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_THROTTLED);
        $this->artisan('createAdmin', [
            '--name' => 'Platform Admin', '--email' => 'admin@example.test', '--no-interaction' => true,
        ])->assertFailed();
        $this->assertTrue(User::firstOrFail()->isSuperAdmin());
    }

    public function test_no_interaction_option_overrides_an_interactive_input_in_a_remote_runner(): void
    {
        Notification::fake();
        $password = bin2hex(random_bytes(32));
        Str::createRandomStringsUsing(fn () => $password);

        try {
            $command = Artisan::all()['createAdmin'];
            $input = new ArrayInput([
                '--name' => 'Cloud Administrator',
                '--email' => 'cloud-admin@example.test',
                '--no-interaction' => true,
            ]);
            $input->setInteractive(true);
            $output = new BufferedOutput;

            $this->assertSame(0, $command->run($input, $output));
            $user = User::where('email', 'cloud-admin@example.test')->firstOrFail();
            $this->assertTrue($user->isSuperAdmin());
            $this->assertNull($user->organization_id);
            $this->assertNotSame($password, $user->password);
            $this->assertTrue(Hash::check($password, $user->password));
            $text = $output->fetch();
            $this->assertStringNotContainsString($password, $text);
            $this->assertStringNotContainsString('Confirm password', $text);
            Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user, $text) {
                $this->assertTrue(Password::tokenExists($user, $notification->token));
                $this->assertStringNotContainsString($notification->token, $text);

                return true;
            });
        } finally {
            Str::createRandomStringsNormally();
        }
    }

    public function test_remote_runner_with_interactive_input_and_missing_options_fails_without_prompts(): void
    {
        Notification::fake();
        $command = Artisan::all()['createAdmin'];
        $input = new ArrayInput(['--no-interaction' => true]);
        $input->setInteractive(true);
        $output = new BufferedOutput;

        $this->assertSame(1, $command->run($input, $output));
        $this->assertStringContainsString('requires both --name and --email', $output->fetch());
        $this->assertDatabaseCount('users', 0);
        Notification::assertNothingSent();
    }
}
