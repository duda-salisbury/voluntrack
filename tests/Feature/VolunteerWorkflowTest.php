<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Organization;
use App\Models\SpecialEvent;
use App\Models\User;
use App\Models\VolunteerEntry;
use App\Notifications\AdministratorInvitation;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class VolunteerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function organization(string $slug = 'qcnemba'): Organization
    {
        return Organization::create([
            'name' => $slug === 'qcnemba' ? 'QC NEMBA' : 'Another Organization',
            'slug' => $slug,
            'is_active' => true,
            'accent_color' => '#245744',
        ]);
    }

    private function administrator(?Organization $organization = null): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'organization_id' => $organization?->id,
        ]);
    }

    private function location(Organization $organization): Location
    {
        return Location::create([
            'organization_id' => $organization->id,
            'name' => 'Pachaug State Forest',
            'slug' => 'pachaug',
            'is_active' => true,
        ]);
    }

    private function event(Organization $organization): SpecialEvent
    {
        return SpecialEvent::create([
            'organization_id' => $organization->id,
            'name' => 'Fall Trail Cleanup 2026',
            'starts_on' => '2026-10-01',
            'is_active' => true,
        ]);
    }

    private function entry(Organization $organization, Location $location, array $attributes = []): VolunteerEntry
    {
        return VolunteerEntry::create([
            'organization_id' => $organization->id,
            'location_id' => $location->id,
            'volunteered_at' => '2026-10-01',
            'hours' => 4,
            'source' => 'manual',
            ...$attributes,
        ]);
    }

    private function submission(Location $location, array $attributes = []): array
    {
        return [
            'location_id' => $location->id,
            'volunteered_at' => now()->toDateString(),
            'hours' => 4,
            ...$attributes,
        ];
    }

    public function test_platform_administrator_creates_an_organization_and_lands_on_onboarding(): void
    {
        $response = $this->actingAs($this->administrator())->post(route('admin.organizations.store'), [
            'name' => 'QC NEMBA',
            'slug' => 'qcnemba',
        ]);
        $organization = Organization::where('slug', 'qcnemba')->firstOrFail();
        $response->assertRedirect(route('admin.organizations.show', $organization));
        $this->get($response->headers->get('Location'))->assertOk()
            ->assertSee('/qcnemba/log-hours')->assertSee('Send secure invitation');
        $this->assertTrue($organization->is_active);
        $this->assertSame('Log Your Volunteer Hours', $organization->config->welcomeTitle);
    }

    public function test_reserved_slugs_and_cross_organization_onboarding_are_rejected(): void
    {
        $this->actingAs($this->administrator())->post(route('admin.organizations.store'), [
            'name' => 'Reserved', 'slug' => 'admin',
        ])->assertSessionHasErrors('slug');
        $organization = $this->organization();
        $other = $this->organization('other');
        $this->actingAs($this->administrator($organization))
            ->get(route('admin.organizations.show', $other))->assertForbidden();
        $this->post(route('admin.organizations.administrators', $other), [
            'name' => 'Intruder', 'email' => 'intruder@example.test',
        ])->assertForbidden();
    }

    public function test_secure_invitation_sets_password_and_allows_organization_admin_login(): void
    {
        Notification::fake();
        $organization = $this->organization();
        $this->actingAs($this->administrator())->post(route('admin.organizations.administrators', $organization), [
            'name' => 'QC Administrator', 'email' => 'qc-admin@example.test',
        ])->assertRedirect(route('admin.organizations.show', $organization));
        $administrator = User::where('email', 'qc-admin@example.test')->firstOrFail();
        $this->assertTrue($administrator->is_admin);
        $this->assertSame($organization->id, $administrator->organization_id);
        $token = null;
        Notification::assertSentTo($administrator, AdministratorInvitation::class, function ($notification) use (&$token, $administrator) {
            $token = $notification->token;
            $mail = $notification->toMail($administrator);
            $this->assertStringContainsString('/account/password/reset/', $mail->actionUrl);
            $this->assertStringContainsString('QC NEMBA', $mail->subject);

            return true;
        });
        auth()->logout();
        $this->get(route('account.password.reset', ['token' => $token, 'email' => $administrator->email]))->assertOk();
        $this->post(route('account.password.update'), [
            'token' => $token,
            'email' => $administrator->email,
            'password' => 'a-secure-password-123',
            'password_confirmation' => 'a-secure-password-123',
        ])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('a-secure-password-123', $administrator->fresh()->password));
        $this->post(route('login.post'), ['email' => $administrator->email, 'password' => 'a-secure-password-123'])
            ->assertRedirect(route('admin.overview'));
        $this->get(route('admin.overview'))->assertOk()->assertSee('QC NEMBA');
    }

    public function test_existing_volunteer_can_be_assigned_without_moving_another_organizations_account(): void
    {
        Notification::fake();
        $organization = $this->organization();
        $other = $this->organization('other');
        $volunteer = User::factory()->create(['is_admin' => false, 'organization_id' => $organization->id]);
        $foreignUser = User::factory()->create(['is_admin' => true, 'organization_id' => $other->id]);
        $this->actingAs($this->administrator());
        $this->post(route('admin.organizations.administrators', $organization), [
            'name' => $volunteer->name, 'email' => $volunteer->email,
        ])->assertSessionHasNoErrors();
        $this->assertTrue($volunteer->fresh()->is_admin);
        $this->post(route('admin.organizations.administrators', $organization), [
            'name' => $foreignUser->name, 'email' => $foreignUser->email,
        ])->assertSessionHasErrors('email');
        $this->assertSame($other->id, $foreignUser->fresh()->organization_id);
    }

    public function test_invitation_link_is_single_use_and_invalid_tokens_cannot_activate_accounts(): void
    {
        $organization = $this->organization();
        $user = $this->administrator($organization);
        $token = Password::createToken($user);
        $payload = ['email' => $user->email, 'token' => 'not-a-valid-token', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->post(route('account.password.update'), $payload)->assertSessionHasErrors('email');
        $payload['token'] = $token;
        $this->post(route('account.password.update'), $payload)->assertRedirect(route('login'));
        $this->post(route('account.password.update'), $payload)->assertSessionHasErrors('email');
    }

    public function test_password_reset_notification_uses_the_real_reset_route(): void
    {
        $user = $this->administrator($this->organization());
        $mail = (new ResetPassword('example-token'))->toMail($user);
        $this->assertStringContainsString('/account/password/reset/example-token', $mail->actionUrl);
    }

    public function test_branding_is_applied_to_the_entire_public_workflow_and_toggles_can_be_disabled(): void
    {
        $organization = $this->organization();
        $location = $this->location($organization);
        $this->actingAs($this->administrator($organization))->put(route('admin.branding.update'), [
            'name' => 'QC NEMBA',
            'accent_color' => '#345678',
            'welcome_title' => 'Give back outdoors',
            'welcome_message' => 'Record your time with our crew.',
            'submit_button_text' => 'Record my contribution',
            'success_message' => 'Our trails thank you!',
            'label_name' => 'Volunteer name',
            'label_location' => 'Forest',
            'label_date' => 'Work date',
            'label_hours' => 'Time contributed',
            'require_name' => 0,
            'show_notes' => 0,
            'show_hours_hint' => 0,
            'organization_id' => $this->organization('other')->id,
        ])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get(route('org.hours.index', $organization))->assertOk()
            ->assertSee('Give back outdoors')->assertSee('Record my contribution')->assertSee('Forest')
            ->assertDontSee('name="notes"', false)->assertDontSee('Enter hours as a decimal');
        $this->assertFalse($organization->fresh()->config->showHoursHint);
        $this->followingRedirects()->post(route('org.hours.store', $organization), $this->submission($location))
            ->assertOk()->assertSee('Our trails thank you!');
    }

    public function test_public_submissions_allow_anonymous_named_and_event_contributions_without_exposing_matching(): void
    {
        $organization = $this->organization();
        $location = $this->location($organization);
        $event = $this->event($organization);
        $token = (string) Str::uuid();
        $this->get(route('org.hours.index', ['org' => $organization, 'event' => $event->id]))
            ->assertOk()->assertSee('Fall Trail Cleanup 2026')->assertSee('value="'.$event->id.'" selected', false)
            ->assertDontSee('is_matched')->assertDontSee('Matching');
        $this->post(route('org.hours.store', $organization), $this->submission($location, [
            'anon_token' => $token, 'is_matched' => true, 'source' => 'admin',
        ]))->assertRedirect(route('org.hours.success', $organization));
        $this->assertDatabaseHas('volunteer_entries', ['anon_token' => $token, 'special_event_id' => null, 'is_matched' => false, 'source' => 'manual']);
        $this->post(route('org.hours.store', $organization), $this->submission($location, [
            'name' => 'Pat Volunteer', 'anon_token' => $token, 'special_event_id' => $event->id,
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('volunteer_entries', [
            'name' => 'Pat Volunteer', 'organization_id' => $organization->id,
            'special_event_id' => $event->id, 'location_id' => $location->id,
        ]);
        $this->get(route('org.hours.success', $organization))->assertOk()->assertSee('Pat Volunteer')->assertSee('Fall Trail Cleanup 2026');
        $this->get('/qcnemba')->assertOk();
    }

    public function test_public_htmx_uses_full_responses_and_preserves_invalid_input(): void
    {
        $organization = $this->organization();
        $location = $this->location($organization);
        $url = route('org.hours.index', $organization);
        $this->followingRedirects()->withHeader('HX-Request', 'true')->from($url)
            ->post(route('org.hours.store', $organization), $this->submission($location, ['hours' => 25, 'name' => 'Keep my name']))
            ->assertOk()->assertSee('Keep my name')->assertSee('A few details need your attention');
        $this->followingRedirects()->withHeader('HX-Request', 'true')->post(route('org.hours.store', $organization), $this->submission($location))
            ->assertOk()->assertSee('id="form-container"', false)->assertSee('Log Another Entry')->assertSee('Create an optional account');
    }

    public function test_public_form_rejects_cross_org_inactive_and_deleted_locations_or_events(): void
    {
        $organization = $this->organization();
        $other = $this->organization('other');
        $location = $this->location($organization);
        $foreignLocation = $this->location($other);
        $event = $this->event($organization);
        $foreignEvent = $this->event($other);
        $url = route('org.hours.store', $organization);
        $this->post($url, $this->submission($foreignLocation))->assertSessionHasErrors('location_id');
        $this->post($url, $this->submission($location, ['special_event_id' => $foreignEvent->id]))->assertSessionHasErrors('special_event_id');
        $event->update(['is_active' => false]);
        $this->post($url, $this->submission($location, ['special_event_id' => $event->id]))->assertSessionHasErrors('special_event_id');
        $location->update(['is_active' => false]);
        $this->post($url, $this->submission($location))->assertSessionHasErrors('location_id');
        $location->update(['is_active' => true]);
        $location->delete();
        $this->post($url, $this->submission($location))->assertSessionHasErrors('location_id');
        $this->assertDatabaseCount('volunteer_entries', 0);
    }

    public function test_logged_in_volunteer_submission_links_only_their_own_organization(): void
    {
        $organization = $this->organization();
        $other = $this->organization('other');
        $volunteer = User::factory()->create(['is_admin' => false, 'organization_id' => $organization->id]);
        $this->actingAs($volunteer)->post(route('org.hours.store', $organization), $this->submission($this->location($organization), ['anon_token' => (string) Str::uuid()]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('volunteer_entries', ['volunteer_user_id' => $volunteer->id, 'anon_token' => null, 'name' => $volunteer->name]);
        $this->post(route('org.hours.store', $other), $this->submission($this->location($other)))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('volunteer_entries', ['organization_id' => $other->id, 'volunteer_user_id' => null]);
    }

    public function test_organization_admin_creates_location_and_special_event_without_accepting_a_spoofed_org(): void
    {
        $organization = $this->organization();
        $other = $this->organization('other');
        $this->actingAs($this->administrator($organization));
        $this->post(route('admin.locations.store'), ['name' => 'Pachaug', 'organization_id' => $other->id, 'is_active' => 1])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.special-events.store'), [
            'name' => 'Fall Cleanup', 'starts_on' => '2026-10-01', 'ends_on' => '2026-10-02',
            'organization_id' => $other->id, 'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $event = SpecialEvent::firstOrFail();
        $this->assertSame($organization->id, $event->organization_id);
        $this->assertDatabaseHas('locations', ['name' => 'Pachaug', 'organization_id' => $organization->id]);
        $this->get(route('admin.special-events.show', $event))->assertOk()->assertSee('Copy event link');
        $this->put(route('admin.special-events.update', $event), [
            'name' => 'Fall Cleanup', 'starts_on' => '2026-10-01', 'is_active' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertFalse($event->fresh()->is_active);
    }

    public function test_event_date_range_is_validated_and_cross_organization_event_access_is_blocked(): void
    {
        $organization = $this->organization();
        $other = $this->organization('other');
        $event = $this->event($other);
        $this->actingAs($this->administrator($organization));
        $this->post(route('admin.special-events.store'), [
            'name' => 'Invalid dates', 'starts_on' => '2026-10-02', 'ends_on' => '2026-10-01', 'is_active' => 1,
        ])->assertSessionHasErrors('ends_on');
        $this->get(route('admin.special-events.show', $event))->assertForbidden();
        $this->get(route('admin.special-events.edit', $event))->assertForbidden();
        $this->put(route('admin.special-events.update', $event), [])->assertForbidden();
    }

    public function test_every_entry_mutation_is_organization_authorized(): void
    {
        $organization = $this->organization();
        $other = $this->organization('other');
        $entry = $this->entry($other, $this->location($other));
        $this->actingAs($this->administrator($organization));
        $this->get(route('admin.entries.edit', $entry))->assertForbidden();
        $this->put(route('admin.entries.update', $entry), [])->assertForbidden();
        $this->patch(route('admin.entries.update-inline', $entry), ['field' => 'hours', 'value' => 9])->assertForbidden();
        $this->post(route('admin.entries.toggle-match', $entry))->assertForbidden();
        $this->post(route('admin.entries.update-notes', $entry), ['notes' => 'No access'])->assertForbidden();
        $this->delete(route('admin.entries.destroy', $entry))->assertForbidden();
        $this->assertSame('4.00', $entry->fresh()->hours);
    }

    public function test_admin_entry_relationships_cannot_cross_organizations_and_matching_can_be_cleared(): void
    {
        $organization = $this->organization();
        $other = $this->organization('other');
        $location = $this->location($organization);
        $entry = $this->entry($organization, $location, ['is_matched' => true]);
        $foreignVolunteer = User::factory()->create(['organization_id' => $other->id, 'is_admin' => false]);
        $this->actingAs($this->administrator($organization));
        $this->put(route('admin.entries.update', $entry), $this->submission($this->location($other)))
            ->assertSessionHasErrors('location_id');
        $this->post(route('admin.entries.store'), $this->submission($location, ['volunteer_user_id' => $foreignVolunteer->id]))
            ->assertSessionHasErrors('volunteer_user_id');
        $this->put(route('admin.entries.update', $entry), $this->submission($location, ['special_event_id' => $this->event($other)->id]))
            ->assertSessionHasErrors('special_event_id');
        $this->put(route('admin.entries.update', $entry), $this->submission($location))->assertSessionHasNoErrors();
        $this->assertFalse($entry->fresh()->is_matched);
        $this->patchJson(route('admin.entries.update-inline', $entry), ['field' => 'hours', 'value' => 30])->assertUnprocessable();
    }

    public function test_reports_apply_all_filters_without_double_counting_and_exclude_other_organizations(): void
    {
        $organization = $this->organization();
        $other = $this->organization('other');
        $location = $this->location($organization);
        $event = $this->event($organization);
        $volunteer = User::factory()->create(['is_admin' => false, 'organization_id' => $organization->id]);
        $this->entry($organization, $location, ['special_event_id' => $event->id, 'volunteer_user_id' => $volunteer->id, 'name' => $volunteer->name, 'is_matched' => true]);
        $this->entry($organization, $location, ['hours' => 2, 'anon_token' => (string) Str::uuid()]);
        $this->entry($organization, $location, ['hours' => 3, 'source' => 'admin', 'volunteered_at' => '2026-09-01']);
        $this->entry($other, $this->location($other), ['hours' => 20, 'name' => 'Private Volunteer']);
        $this->actingAs($this->administrator($organization));
        $this->get(route('admin.reports'))->assertOk()->assertDontSee('Private Volunteer')
            ->assertViewHas('stats', fn ($stats) => $stats['total_hours'] === 9.0 && $stats['matched_hours'] === 4.0 && $stats['event_hours'] === 4.0)
            ->assertViewHas('byLocation', fn ($rows) => (float) $rows->sum('total') === 9.0)
            ->assertViewHas('byEvent', fn ($rows) => (float) $rows->sum('total') === 9.0)
            ->assertViewHas('byMonth', fn ($rows) => $rows->sum() === 9.0);
        $filters = [
            'location_id' => $location->id, 'special_event_id' => $event->id,
            'date_from' => '2026-10-01', 'date_to' => '2026-10-31', 'source' => 'manual',
            'volunteer_user_id' => $volunteer->id, 'matched' => 'yes',
        ];
        foreach (['admin.dashboard', 'admin.reports', 'admin.stats'] as $route) {
            $this->get(route($route, $filters))->assertOk()
                ->assertViewHas('stats', fn ($stats) => $stats['total_hours'] === 4.0 && $stats['total_entries'] === 1);
        }
        $this->get(route('admin.reports', ['event_status' => 'ordinary']))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total_hours'] === 5.0);
        $event->update(['is_active' => false]);
        $this->get(route('admin.reports', ['special_event_id' => $event->id]))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total_hours'] === 4.0);
    }

    public function test_volunteer_estimate_deduplicates_identifiers_and_explains_unidentified_entries(): void
    {
        $organization = $this->organization();
        $location = $this->location($organization);
        $token = (string) Str::uuid();
        $this->entry($organization, $location, ['anon_token' => $token]);
        $this->entry($organization, $location, ['anon_token' => $token, 'name' => 'Same browser']);
        $this->entry($organization, $location, ['name' => 'Pat']);
        $this->entry($organization, $location, ['name' => ' PAT ']);
        $this->entry($organization, $location);
        $this->actingAs($this->administrator($organization))->get(route('admin.reports'))
            ->assertOk()->assertSee('An estimate, not a headcount')
            ->assertViewHas('stats', fn ($stats) => $stats['estimated_volunteers'] === 2 && $stats['unidentified_entries'] === 1);
    }

    public function test_admin_views_are_scoped_and_volunteers_cannot_access_administration(): void
    {
        $organization = $this->organization();
        $other = $this->organization('other');
        $ownLocation = $this->location($organization);
        $foreignLocation = $this->location($other);
        $foreignLocation->update(['name' => 'Private location']);
        $foreignEvent = $this->event($other);
        $foreignEvent->update(['name' => 'Private event']);
        $foreignUser = $this->administrator($other);
        $this->actingAs($this->administrator($organization));
        foreach (['admin.overview', 'admin.dashboard', 'admin.reports', 'admin.locations.index', 'admin.special-events.index', 'admin.users.index', 'admin.branding.edit', 'admin.entries.create'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('Private location')->assertDontSee('Private event')->assertDontSee($foreignUser->email);
        }
        $this->get(route('admin.locations.edit', $foreignLocation))->assertForbidden();
        $this->get(route('admin.users.edit', $foreignUser))->assertForbidden();
        $this->get(route('admin.reports', ['special_event_id' => $foreignEvent->id]))->assertSessionHasErrors('special_event_id');
        $this->get(route('admin.reports', ['date_from' => '2026-10-02', 'date_to' => '2026-10-01']))->assertSessionHasErrors('date_to');
        $this->actingAs(User::factory()->create(['is_admin' => false, 'organization_id' => $organization->id]));
        $this->get(route('admin.overview'))->assertRedirect(route('login'));
    }

    public function test_inactive_organizations_are_unavailable_and_manifest_has_org_specific_start_url(): void
    {
        $organization = $this->organization();
        $this->get(route('org.manifest', $organization))->assertOk()->assertJsonPath('start_url', route('org.hours.index', $organization))->assertJsonPath('display', 'standalone');
        $admin = $this->administrator($organization);
        $organization->update(['is_active' => false]);
        $this->get(route('org.hours.index', $organization))->assertNotFound();
        $this->get(route('org.hours.success', $organization))->assertNotFound();
        $this->get(route('org.manifest', $organization))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.overview'))->assertForbidden();
    }

    public function test_event_links_reject_unavailable_or_foreign_events_without_redirect_loops(): void
    {
        $organization = $this->organization();
        $event = $this->event($organization);
        $foreignEvent = $this->event($this->organization('other'));
        $this->get(route('org.hours.index', ['org' => $organization, 'event' => $foreignEvent->id]))->assertNotFound();
        $event->update(['is_active' => false]);
        $this->get(route('org.hours.index', ['org' => $organization, 'event' => $event->id]))->assertNotFound();
        $this->get(route('org.hours.index', $organization))->assertOk();
    }

    public function test_required_name_and_optional_notes_settings_match_server_validation(): void
    {
        $organization = $this->organization();
        $organization->update(['settings' => ['form' => ['require_name' => true, 'show_notes' => true]]]);
        $location = $this->location($organization);
        $this->get(route('org.hours.index', $organization))->assertOk()->assertSee('name="notes"', false)->assertSee('Enter hours as a decimal');
        $this->post(route('org.hours.store', $organization), $this->submission($location))->assertSessionHasErrors('name');
        $this->post(route('org.hours.store', $organization), $this->submission($location, [
            'name' => 'Trail volunteer', 'notes' => 'Cleared fallen branches.',
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('volunteer_entries', ['name' => 'Trail volunteer', 'notes' => 'Cleared fallen branches.']);
    }

    public function test_bright_and_dark_brand_colors_choose_readable_button_text(): void
    {
        $organization = $this->organization();
        foreach (['#ffff00' => '#000000', '#ffffff' => '#000000', '#174b3c' => '#ffffff', '#000000' => '#ffffff'] as $background => $foreground) {
            $organization->update(['accent_color' => $background]);
            $this->assertSame($foreground, $organization->accent_text_color);
            $this->get(route('org.hours.index', $organization))->assertOk()->assertSee('--bs-btn-color:               '.$foreground, false);
        }
    }

    public function test_archiving_organization_keeps_hours_and_blocks_its_workspace(): void
    {
        $organization = $this->organization();
        $entry = $this->entry($organization, $this->location($organization));
        $admin = $this->administrator($organization);
        $this->actingAs($this->administrator())->delete(route('admin.organizations.destroy', $organization))
            ->assertRedirect(route('admin.organizations.index'));
        $this->assertSoftDeleted($organization);
        $this->assertDatabaseHas('volunteer_entries', ['id' => $entry->id, 'deleted_at' => null]);
        $this->get(route('org.hours.index', $organization))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.overview'))->assertForbidden();
    }

    public function test_platform_cannot_move_a_location_with_recorded_hours_to_another_organization(): void
    {
        $organization = $this->organization();
        $other = $this->organization('other');
        $location = $this->location($organization);
        $this->entry($organization, $location);
        $this->actingAs($this->administrator())->put(route('admin.locations.update', $location), [
            'name' => $location->name, 'organization_id' => $other->id, 'is_active' => true,
        ])->assertSessionHasErrors('organization_id');
        $this->assertSame($organization->id, $location->fresh()->organization_id);
    }

    public function test_expired_activation_tokens_cannot_set_a_password(): void
    {
        $user = $this->administrator($this->organization());
        $token = Password::createToken($user);
        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();
        $this->post(route('account.password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');
    }

    public function test_optional_accounts_can_register_and_view_only_their_own_event_history(): void
    {
        $organization = $this->organization();
        $location = $this->location($organization);
        $event = $this->event($organization);
        $this->post(route('org.account.register.post', $organization), [
            'name' => 'Registered Volunteer', 'email' => 'volunteer@example.test',
            'password' => 'a-volunteer-password', 'password_confirmation' => 'a-volunteer-password',
        ])->assertRedirect(route('org.hours.index', $organization));
        $this->assertAuthenticated();
        $volunteer = User::where('email', 'volunteer@example.test')->firstOrFail();
        $this->assertFalse($volunteer->is_admin);
        $this->post(route('org.hours.store', $organization), $this->submission($location, ['special_event_id' => $event->id]))
            ->assertSessionHasNoErrors();
        $this->entry($organization, $location, ['name' => 'Another private volunteer', 'hours' => 10]);
        $this->get(route('org.account.history', $organization))->assertOk()
            ->assertSee('Fall Trail Cleanup 2026')->assertDontSee('Another private volunteer')
            ->assertViewHas('totalHours', fn ($hours) => (float) $hours === 4.0);
        $this->post(route('org.account.logout', $organization))->assertRedirect(route('org.hours.index', $organization));
        $this->assertGuest();
        $this->get(route('org.account.history', $organization))->assertRedirect(route('org.account.login', $organization));
        $this->post(route('org.account.login.post', $organization), [
            'email' => 'volunteer@example.test', 'password' => 'a-volunteer-password',
        ])->assertRedirect(route('org.hours.index', $organization));
        $this->assertAuthenticatedAs($volunteer);
    }

    public function test_confirmation_requires_a_submission_in_the_current_session_and_organization(): void
    {
        $organization = $this->organization();
        $this->get(route('org.hours.success', $organization))->assertRedirect(route('org.hours.index', $organization));
        $other = $this->organization('other');
        $entry = $this->entry($other, $this->location($other));
        $this->withSession(['submitted_entry_id' => $entry->id])->get(route('org.hours.success', $organization))
            ->assertRedirect(route('org.hours.index', $organization));
    }
}
