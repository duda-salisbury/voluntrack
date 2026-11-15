<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Location;
use App\Models\Organization;
use App\Models\SpecialEvent;
use App\Models\User;
use App\Models\VolunteerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class AuditActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function organization(string $slug): Organization
    {
        return Organization::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true, 'accent_color' => '#245744']);
    }

    private function location(Organization $organization, string $name = 'Forest'): Location
    {
        return Location::create(['organization_id' => $organization->id, 'name' => $name, 'slug' => strtolower($name), 'is_active' => true]);
    }

    private function admin(Organization $organization): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'admin']);
        return $user;
    }

    public function test_authentication_events_are_global_and_do_not_store_credentials(): void
    {
        $user = User::factory()->create(['password' => Hash::make('safe-password-123')]);
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'wrong-secret'])
            ->assertSessionHasErrors('email');
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'safe-password-123'])->assertRedirect();
        $this->post(route('logout'))->assertRedirect();
        $this->assertDatabaseHas('audit_events', ['event_type' => 'auth.failed', 'actor_id' => null, 'organization_id' => null]);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'auth.login', 'actor_id' => $user->id, 'organization_id' => null]);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'auth.logout', 'actor_id' => $user->id, 'organization_id' => null]);
        $records = AuditEvent::where('category', 'authentication')->get()->toJson();
        $this->assertStringNotContainsString('wrong-secret', $records);
        $this->assertStringNotContainsString('safe-password-123', $records);
        $this->assertStringNotContainsString($user->email, $records);
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $this->actingAs($platform)->post(route('account.platform'));
        $this->get(route('admin.users.index'))->assertOk()->assertViewHas('users', fn ($users) =>
            $users->firstWhere('id', $user->id)?->last_login_at !== null);
    }

    public function test_activity_view_is_scoped_and_authentication_is_platform_only(): void
    {
        $alpha = $this->organization('alpha');
        $beta = $this->organization('beta');
        $admin = $this->admin($alpha);
        $this->location($alpha, 'AlphaForest');
        $this->location($beta, 'BetaForest');
        AuditEvent::create(['event_type' => 'auth.failed', 'category' => 'authentication', 'description' => 'Failed sign-in attempt',
            'ip_address' => '192.0.2.1', 'created_at' => now()]);

        $this->actingAs($admin)->get(route('admin.activity.index', ['organization_id' => $beta->id]))
            ->assertOk()->assertSee('AlphaForest')->assertDontSee('BetaForest')
            ->assertDontSee('Failed sign-in attempt')->assertDontSee('192.0.2.1');
        $this->get(route('admin.activity.index', ['category' => 'authentication']))->assertSessionHasErrors('category');
        $this->get(route('admin.users.index'))->assertOk()->assertDontSee('Last sign-in:');
        $volunteer = User::factory()->create();
        $volunteer->organizations()->attach($alpha, ['role' => 'volunteer']);
        $this->actingAs($volunteer)->get(route('admin.activity.index'))->assertForbidden();

        $platform = User::factory()->create(['is_platform_admin' => true]);
        $this->actingAs($platform)->post(route('account.platform'));
        $this->get(route('admin.activity.index'))->assertOk()
            ->assertSee('AlphaForest')->assertSee('BetaForest')->assertSee('Failed sign-in attempt')->assertSee('192.0.2.1');
        $this->get(route('admin.activity.index', ['organization_id' => $beta->id]))->assertOk()
            ->assertSee('BetaForest')->assertDontSee('AlphaForest')->assertDontSee('Failed sign-in attempt');
        $this->get(route('admin.users.index'))->assertOk()->assertSee('Last sign-in:');
    }

    public function test_registration_profile_and_password_events_are_global_and_exclude_secrets(): void
    {
        $organization = $this->organization('alpha');
        $this->post(route('org.account.register.post', $organization), [
            'name' => 'New volunteer', 'email' => 'volunteer@example.test',
            'password' => 'initial-secret-123', 'password_confirmation' => 'initial-secret-123',
        ])->assertRedirect();
        $user = User::where('email', 'volunteer@example.test')->firstOrFail();
        $this->get(route('account.profile'))->assertOk()->assertSee('Last sign-in:')->assertDontSee('None in the past year');
        $this->put(route('account.profile.update'), [
            'name' => 'Updated volunteer', 'email' => $user->email, 'current_password' => 'initial-secret-123',
        ])->assertSessionHasNoErrors();
        $this->put(route('account.profile.password'), [
            'current_password' => 'initial-secret-123', 'password' => 'changed-secret-123', 'password_confirmation' => 'changed-secret-123',
        ])->assertSessionHasNoErrors();
        $token = Password::createToken($user);
        $this->post(route('account.password.update'), [
            'email' => $user->email, 'token' => $token, 'password' => 'reset-secret-123', 'password_confirmation' => 'reset-secret-123',
        ])->assertRedirect(route('login'));
        foreach (['account.registered', 'account.profile_updated', 'account.password_changed', 'account.password_reset'] as $type) {
            $this->assertDatabaseHas('audit_events', ['event_type' => $type, 'actor_id' => $user->id, 'organization_id' => null]);
        }
        $this->assertDatabaseHas('audit_events', ['event_type' => 'membership.added', 'organization_id' => $organization->id, 'subject_user_id' => $user->id]);
        $records = AuditEvent::all()->toJson();
        foreach (['initial-secret-123', 'changed-secret-123', 'reset-secret-123', $token] as $secret) {
            $this->assertStringNotContainsString($secret, $records);
        }
    }

    public function test_membership_hours_events_and_exports_create_scoped_events(): void
    {
        Notification::fake();
        $organization = $this->organization('alpha');
        $admin = $this->admin($organization);
        $volunteer = User::factory()->create(['name' => 'Volunteer']);
        $volunteer->organizations()->attach($organization, ['role' => 'volunteer']);
        $location = $this->location($organization);
        $this->actingAs($admin);
        $this->post(route('admin.organizations.administrators', $organization), ['name' => $volunteer->name, 'email' => $volunteer->email])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('audit_events', ['event_type' => 'membership.admin_invited', 'actor_id' => $admin->id,
            'organization_id' => $organization->id, 'subject_user_id' => $volunteer->id]);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'membership.role_changed', 'organization_id' => $organization->id]);

        $event = SpecialEvent::create(['organization_id' => $organization->id, 'name' => 'Cleanup', 'starts_on' => '2026-10-01', 'is_active' => true]);
        $event->update(['name' => 'Trail cleanup']);
        $location->update(['name' => 'Updated forest']);
        $otherLocation = $this->location($organization, 'Meadow');
        $otherLocation->delete();
        $entry = VolunteerEntry::create(['organization_id' => $organization->id, 'location_id' => $location->id,
            'volunteered_at' => now()->toDateString(), 'hours' => 3.5, 'source' => 'admin', 'notes' => 'private volunteer note']);
        $entry->update(['hours' => 4]);
        $entry->delete();
        $this->get(route('admin.reports.export'))->assertOk()->streamedContent();

        $organization->update(['is_active' => false, 'settings' => ['form' => ['show_notes' => false]]]);
        foreach (['event.created', 'event.updated', 'location.updated', 'location.deleted', 'entry.admin_created', 'entry.updated',
            'entry.deleted', 'report.exported', 'organization.deactivated', 'organization.updated'] as $type) {
            $this->assertDatabaseHas('audit_events', ['event_type' => $type, 'actor_id' => $admin->id, 'organization_id' => $organization->id]);
        }
        $this->assertStringNotContainsString('private volunteer note', AuditEvent::all()->toJson());
        $this->assertSame(['Hours'], AuditEvent::where('event_type', 'entry.updated')->firstOrFail()->metadata['changes']);
        $organization->update(['is_active' => true]);
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $this->actingAs($platform)->post(route('account.platform'));
        $this->delete(route('admin.users.destroy', $volunteer), ['organization_id' => $organization->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('audit_events', ['event_type' => 'membership.removed', 'actor_id' => $platform->id,
            'organization_id' => $organization->id, 'subject_user_id' => $volunteer->id]);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'membership.role_changed', 'subject_user_id' => $volunteer->id]);
        $this->post(route('logout'));
        $this->post(route('org.hours.store', $organization), [
            'location_id' => $location->id, 'volunteered_at' => now()->toDateString(), 'hours' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('audit_events', ['event_type' => 'entry.submitted', 'actor_id' => null, 'organization_id' => $organization->id]);
    }

    public function test_failed_audit_write_does_not_prevent_volunteer_submission(): void
    {
        $organization = $this->organization('alpha');
        $location = $this->location($organization);
        Log::shouldReceive('error')->atLeast()->once();
        AuditEvent::creating(fn () => throw new RuntimeException('Audit unavailable'));
        try {
            $this->post(route('org.hours.store', $organization), [
                'location_id' => $location->id, 'volunteered_at' => now()->toDateString(), 'hours' => 2,
            ])->assertRedirect(route('org.hours.success', $organization));
            $this->assertDatabaseHas('volunteer_entries', ['organization_id' => $organization->id, 'hours' => 2]);
        } finally {
            AuditEvent::flushEventListeners();
        }
    }

    public function test_rolled_back_changes_do_not_create_audit_events(): void
    {
        $organization = $this->organization('alpha');
        $location = $this->location($organization);
        DB::beginTransaction();
        VolunteerEntry::create(['organization_id' => $organization->id, 'location_id' => $location->id,
            'volunteered_at' => now()->toDateString(), 'hours' => 3, 'source' => 'manual']);
        DB::rollBack();
        $this->assertDatabaseMissing('audit_events', ['event_type' => 'entry.submitted', 'organization_id' => $organization->id]);
    }

    public function test_activity_filters_and_pagination_use_scoped_records(): void
    {
        $organization = $this->organization('alpha');
        $admin = $this->admin($organization);
        $person = User::factory()->create(['name' => 'Selected volunteer']);
        for ($i = 0; $i < 27; $i++) {
            AuditEvent::create(['event_type' => 'entry.updated', 'category' => 'hours', 'description' => 'Updated a volunteer hour entry',
                'actor_id' => $admin->id, 'subject_user_id' => $person->id, 'organization_id' => $organization->id, 'created_at' => now()]);
        }
        $this->actingAs($admin)->get(route('admin.activity.index', [
            'category' => 'hours', 'user' => 'Selected volunteer', 'search' => 'Updated',
            'date_to' => now()->toDateString(),
        ]))->assertOk()->assertViewHas('events', fn ($events) => $events->total() === 27 && $events->count() === 25);
        $this->get(route('admin.activity.index', ['category' => 'hours', 'date_to' => now()->subDay()->toDateString()]))
            ->assertOk()->assertViewHas('events', fn ($events) => $events->total() === 0);
    }

    public function test_retention_removes_old_ip_addresses_and_expired_events(): void
    {
        $oldIp = AuditEvent::create(['event_type' => 'auth.login', 'category' => 'authentication', 'description' => 'Signed in successfully',
            'ip_address' => '192.0.2.1', 'created_at' => now()->subDays(31)]);
        $recent = AuditEvent::create(['event_type' => 'auth.failed', 'category' => 'authentication', 'description' => 'Failed sign-in attempt',
            'ip_address' => '192.0.2.2', 'created_at' => now()->subDays(5)]);
        $expired = AuditEvent::create(['event_type' => 'auth.logout', 'category' => 'authentication', 'description' => 'Signed out',
            'created_at' => now()->subDays(366)]);
        $this->artisan('audit:prune')->assertSuccessful();
        $this->assertNull($oldIp->fresh()->ip_address);
        $this->assertSame('192.0.2.2', $recent->fresh()->ip_address);
        $this->assertDatabaseMissing('audit_events', ['id' => $expired->id]);
    }
}
