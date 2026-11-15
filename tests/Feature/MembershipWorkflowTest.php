<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use App\Models\VolunteerEntry;
use App\Notifications\OrganizationAccessGranted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class MembershipWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function org(string $slug): Organization
    {
        return Organization::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true, 'accent_color' => '#245744']);
    }

    private function location(Organization $org): Location
    {
        return Location::create(['organization_id' => $org->id, 'name' => 'Park', 'slug' => 'park', 'is_active' => true]);
    }

    public function test_legacy_accounts_are_migrated_without_changing_users_or_entries(): void
    {
        $migration = require database_path('migrations/2026_11_15_000001_create_organization_memberships.php');
        $migration->down();
        $org = $this->org('alpha');
        $platform = DB::table('users')->insertGetId(['name' => 'Platform', 'email' => 'platform@example.test', 'password' => 'hash', 'is_admin' => true]);
        $admin = DB::table('users')->insertGetId(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'hash', 'is_admin' => true, 'organization_id' => $org->id]);
        $volunteer = DB::table('users')->insertGetId(['name' => 'Volunteer', 'email' => 'volunteer@example.test', 'password' => 'hash', 'is_admin' => false, 'organization_id' => $org->id]);
        $entry = VolunteerEntry::create(['organization_id' => $org->id, 'location_id' => $this->location($org)->id, 'volunteer_user_id' => $volunteer, 'volunteered_at' => now()->toDateString(), 'hours' => 2]);
        $migration->up();
        $this->assertDatabaseHas('users', ['id' => $platform, 'is_platform_admin' => true, 'password' => 'hash']);
        $this->assertDatabaseHas('organization_user', ['user_id' => $admin, 'organization_id' => $org->id, 'role' => 'admin']);
        $this->assertDatabaseHas('organization_user', ['user_id' => $volunteer, 'organization_id' => $org->id, 'role' => 'volunteer']);
        $this->assertDatabaseHas('volunteer_entries', ['id' => $entry->id, 'organization_id' => $org->id, 'volunteer_user_id' => $volunteer]);
    }

    public function test_organization_context_is_checked_for_every_admin_request(): void
    {
        $alpha = $this->org('alpha');
        $beta = $this->org('beta');
        $user = User::factory()->create();
        $user->organizations()->attach($alpha, ['role' => 'admin']);
        $user->organizations()->attach($beta, ['role' => 'volunteer']);
        $alphaLocation = $this->location($alpha);
        $betaLocation = $this->location($beta);
        $this->actingAs($user)->post(route('account.organizations.switch', $alpha))->assertRedirect(route('admin.overview'));
        $this->get(route('admin.locations.edit', $alphaLocation))->assertOk();
        $this->get(route('admin.locations.edit', $betaLocation))->assertForbidden();
        $this->post(route('account.organizations.switch', $beta), ['destination' => 'settings'])->assertForbidden();
        $this->withSession(['admin_organization_id' => $beta->id])->get(route('admin.overview'))->assertForbidden();
        $this->post(route('account.organizations.switch', $beta))->assertRedirect(route('org.account.history', $beta));
        $alpha->update(['is_active' => false]);
        $this->withSession(['admin_organization_id' => $alpha->id])->get(route('admin.overview'))->assertForbidden();
    }

    public function test_global_login_and_explicit_submission_add_membership_without_changing_roles(): void
    {
        $alpha = $this->org('alpha');
        $beta = $this->org('beta');
        $user = User::factory()->create(['password' => Hash::make('password-123')]);
        $user->organizations()->attach($alpha, ['role' => 'admin']);
        $location = $this->location($beta);
        $this->post(route('org.account.login.post', $beta), ['email' => $user->email, 'password' => 'password-123'])->assertRedirect(route('org.hours.index', $beta));
        $this->get(route('org.hours.index', $beta))->assertOk();
        $this->assertDatabaseMissing('organization_user', ['user_id' => $user->id, 'organization_id' => $beta->id]);
        $this->post(route('org.hours.store', $beta), ['location_id' => $location->id, 'volunteered_at' => now()->toDateString(), 'hours' => 2])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('organization_user', ['user_id' => $user->id, 'organization_id' => $beta->id, 'role' => 'volunteer']);
        $this->assertDatabaseHas('organization_user', ['user_id' => $user->id, 'organization_id' => $alpha->id, 'role' => 'admin']);
        $this->assertDatabaseHas('volunteer_entries', ['organization_id' => $beta->id, 'volunteer_user_id' => $user->id]);
        $this->post(route('org.hours.store', $alpha), ['location_id' => $this->location($alpha)->id, 'volunteered_at' => now()->toDateString(), 'hours' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('organization_user', ['user_id' => $user->id, 'organization_id' => $alpha->id, 'role' => 'admin']);
    }

    public function test_existing_account_invitation_and_membership_removal_preserve_global_identity(): void
    {
        Notification::fake();
        $alpha = $this->org('alpha');
        $beta = $this->org('beta');
        $admin = User::factory()->create();
        $admin->organizations()->attach($alpha, ['role' => 'admin']);
        $person = User::factory()->create();
        $person->organizations()->attach($beta, ['role' => 'volunteer']);
        $entry = VolunteerEntry::create(['organization_id' => $alpha->id, 'location_id' => $this->location($alpha)->id, 'volunteer_user_id' => $person->id, 'volunteered_at' => now()->toDateString(), 'hours' => 3]);
        $password = $person->password;
        $this->actingAs($admin)->post(route('account.organizations.switch', $alpha));
        $this->post(route('admin.organizations.administrators', $alpha), ['name' => 'Ignored name', 'email' => $person->email])->assertSessionHasNoErrors();
        Notification::assertSentTo($person, OrganizationAccessGranted::class);
        $this->assertTrue($person->fresh()->hasOrganizationRole($alpha->id, 'admin'));
        $this->assertTrue($person->fresh()->hasOrganizationRole($beta->id, 'volunteer'));
        $this->assertSame($password, $person->fresh()->password);
        $this->put(route('admin.users.update', $person), ['name' => 'Attacker'])->assertForbidden();
        $this->delete(route('admin.users.destroy', $person))->assertForbidden();
        $this->actingAs(User::factory()->create(['is_platform_admin' => true]))->post(route('account.platform'));
        $this->delete(route('admin.users.destroy', $person), ['organization_id' => $alpha->id])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('organization_user', ['organization_id' => $alpha->id, 'user_id' => $person->id]);
        $this->assertDatabaseHas('users', ['id' => $person->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('volunteer_entries', ['id' => $entry->id, 'volunteer_user_id' => $person->id]);
        $this->actingAs($admin)->post(route('account.organizations.switch', $alpha));
        $this->get(route('admin.dashboard', ['volunteer_user_id' => $person->id]))->assertOk()->assertViewHas('entries', fn ($entries) => $entries->total() === 1);
    }

    public function test_personal_profile_and_cross_organization_hours_are_private(): void
    {
        $alpha = $this->org('alpha');
        $beta = $this->org('beta');
        $user = User::factory()->create(['password' => Hash::make('password-123')]);
        $other = User::factory()->create();
        foreach ([$alpha, $beta] as $org) {
            $user->organizations()->attach($org, ['role' => 'volunteer']);
            VolunteerEntry::create(['organization_id' => $org->id, 'location_id' => $this->location($org)->id, 'volunteer_user_id' => $user->id, 'volunteered_at' => now()->toDateString(), 'hours' => 2]);
        }
        VolunteerEntry::create(['organization_id' => $alpha->id, 'location_id' => Location::where('organization_id', $alpha->id)->first()->id, 'volunteer_user_id' => $other->id, 'name' => 'Private other person', 'volunteered_at' => now()->toDateString(), 'hours' => 8]);
        $this->actingAs($user)->get(route('account.hours'))->assertOk()->assertViewHas('totalHours', fn ($total) => (float) $total === 4.0)->assertDontSee('Private other person');
        $this->put(route('account.profile.update'), ['name' => 'Changed', 'email' => $user->email, 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->put(route('account.profile.update'), ['name' => 'Changed', 'email' => $user->email, 'current_password' => 'password-123'])->assertSessionHasNoErrors();
        $this->assertSame('Changed', $user->fresh()->name);
        $this->put(route('account.profile.password'), ['current_password' => 'wrong', 'password' => 'another-password', 'password_confirmation' => 'another-password'])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password-123', $user->fresh()->password));
    }

    public function test_reports_and_group_entries_follow_the_active_organization(): void
    {
        $alpha = $this->org('alpha');
        $beta = $this->org('beta');
        $admin = User::factory()->create();
        $admin->organizations()->attach($alpha, ['role' => 'admin']);
        $admin->organizations()->attach($beta, ['role' => 'volunteer']);
        $alphaLocation = $this->location($alpha);
        $betaLocation = $this->location($beta);
        VolunteerEntry::create(['organization_id' => $beta->id, 'location_id' => $betaLocation->id, 'name' => 'Private beta contribution', 'volunteered_at' => now()->toDateString(), 'hours' => 10]);
        $this->actingAs($admin)->post(route('account.organizations.switch', $alpha));
        $this->get(route('admin.reports'))->assertOk()->assertDontSee('Private beta contribution');
        $this->get(route('admin.dashboard', ['volunteer_user_id' => $admin->id]))->assertOk();
        $payload = ['location_id' => $betaLocation->id, 'volunteered_at' => now()->toDateString(), 'rows' => [['name' => 'Person', 'hours' => 2, 'minutes' => 0]]];
        $this->post(route('admin.entries.group.store'), $payload)->assertSessionHasErrors('location_id');
        $payload['location_id'] = $alphaLocation->id;
        $this->post(route('admin.entries.group.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('volunteer_entries', ['organization_id' => $alpha->id, 'name' => 'Person']);
    }

    public function test_platform_administrator_can_switch_to_membership_and_back(): void
    {
        $org = $this->org('alpha');
        $user = User::factory()->create(['is_platform_admin' => true]);
        $user->organizations()->attach($org, ['role' => 'admin']);
        $this->actingAs($user)->post(route('account.organizations.switch', $org))->assertRedirect(route('admin.overview'));
        $this->get(route('admin.overview'))->assertOk()->assertSee($org->name);
        $this->post(route('account.platform'))->assertRedirect(route('admin.overview'));
        $this->get(route('admin.organizations.index'))->assertOk();
    }

    public function test_failed_new_account_invitation_does_not_leave_partial_account(): void
    {
        $org = $this->org('alpha');
        $admin = User::factory()->create(['is_platform_admin' => true]);
        Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_THROTTLED);
        $this->actingAs($admin)->post(route('admin.organizations.administrators', $org), ['name' => 'New Admin', 'email' => 'new@example.test'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
        $this->assertDatabaseCount('organization_user', 0);
    }
}
