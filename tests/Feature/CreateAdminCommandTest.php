<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
}
