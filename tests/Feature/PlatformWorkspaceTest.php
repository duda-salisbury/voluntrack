<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Location;
use App\Models\Organization;
use App\Models\SpecialEvent;
use App\Models\User;
use App\Models\VolunteerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlatformWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function organization(string $slug, bool $active = true): Organization
    {
        return Organization::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => $active, 'accent_color' => '#245744']);
    }

    private function location(Organization $organization): Location
    {
        return Location::create(['organization_id' => $organization->id, 'name' => $organization->name.' park', 'slug' => 'park', 'is_active' => true]);
    }

    public function test_superadmin_opens_inactive_workspace_without_membership_and_returns_to_directory(): void
    {
        $organization = $this->organization('alpha', false);
        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)->get(route('admin.organizations.index', ['search' => 'Alpha', 'status' => 'inactive']))
            ->assertOk()->assertSee('Open Workspace');
        $this->get(route('admin.organizations.show', $organization))->assertOk()->assertSee('Open Workspace');
        $this->post(route('account.organizations.workspace', $organization))->assertRedirect(route('admin.overview'));
        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseMissing('organization_user', ['organization_id' => $organization->id, 'user_id' => $admin->id]);
        $this->get(route('admin.overview'))->assertOk()->assertSee('Platform Admin')->assertSee('Viewing')
            ->assertSee($organization->name)->assertSee('Return to Platform')->assertSee('This organization is inactive');
        $this->get(route('admin.branding.edit'))->assertOk()->assertSee('Organization settings');
        $this->get(route('org.hours.index', $organization))->assertNotFound();
        $event = AuditEvent::where('event_type', 'workspace.entered')->firstOrFail();
        $this->assertSame($admin->id, $event->actor_id);
        $this->assertSame($organization->id, $event->organization_id);
        $this->assertSame('platform_admin', $event->metadata['actor_role']);

        $this->post(route('account.platform'), ['destination' => 'organizations'])
            ->assertRedirect(route('admin.organizations.index', ['search' => 'Alpha', 'status' => 'inactive']));
        $this->get(route('admin.organizations.index'))->assertOk();
    }

    public function test_superadmin_can_manage_only_selected_organization_and_audits_actual_actor(): void
    {
        Notification::fake();
        $alpha = $this->organization('alpha');
        $beta = $this->organization('beta');
        $alphaLocation = $this->location($alpha);
        $betaLocation = $this->location($beta);
        $betaEvent = SpecialEvent::create(['organization_id' => $beta->id, 'name' => 'Private event', 'starts_on' => today(), 'is_active' => true]);
        $betaEntry = VolunteerEntry::create(['organization_id' => $beta->id, 'location_id' => $betaLocation->id, 'name' => 'Private beta volunteer', 'volunteered_at' => today(), 'hours' => 7]);
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $existingAdmin = User::factory()->create();
        $newAdmin = User::factory()->create();
        $existingAdmin->organizations()->attach($alpha, ['role' => 'admin']);
        $existingAdmin->organizations()->attach($beta, ['role' => 'admin']);

        $this->actingAs($admin)->post(route('account.organizations.workspace', $alpha));
        foreach (['admin.dashboard', 'admin.locations.index', 'admin.special-events.index', 'admin.users.index', 'admin.reports', 'admin.activity.index', 'admin.branding.edit', 'admin.entries.create', 'admin.entries.group', 'admin.entries.group-total'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('Private beta volunteer');
        }
        $this->get(route('admin.locations.edit', $betaLocation))->assertForbidden();
        $this->get(route('admin.special-events.show', $betaEvent))->assertForbidden();
        $this->get(route('admin.entries.edit', $betaEntry))->assertForbidden();
        $this->put(route('admin.entries.update', $betaEntry), ['_admin_workspace' => $alpha->id])->assertForbidden();
        $this->patch(route('admin.locations.toggle-active', $betaLocation), [], ['HX-Request' => 'true', 'X-Admin-Workspace' => (string) $alpha->id])->assertForbidden();
        $this->get(route('admin.organizations.show', $beta))->assertForbidden();
        $this->post(route('admin.organizations.administrators', $beta), ['_admin_workspace' => $alpha->id, 'name' => 'Other', 'email' => 'other@example.test'])->assertForbidden();
        $this->post(route('admin.organizations.administrators', $alpha), ['_admin_workspace' => $alpha->id, 'name' => $newAdmin->name, 'email' => $newAdmin->email])
            ->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('organization_user', ['organization_id' => $alpha->id, 'user_id' => $newAdmin->id, 'role' => 'admin']);
        $this->post(route('admin.entries.group-total.store'), ['_admin_workspace' => $alpha->id, 'location_id' => $betaLocation->id, 'volunteered_at' => today()->toDateString(), 'hours' => 10])->assertSessionHasErrors('location_id');
        $this->post(route('admin.entries.group-total.store'), ['_admin_workspace' => $alpha->id, 'location_id' => $alphaLocation->id, 'volunteered_at' => today()->toDateString(), 'hours' => 10, 'participant_count' => 4])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.dashboard'));
        $entry = VolunteerEntry::where('organization_id', $alpha->id)->firstOrFail();
        $this->assertTrue($entry->is_group_total);
        $this->assertSame($admin->id, $entry->created_by_admin_id);
        $this->put(route('admin.entries.update', $entry), ['_admin_workspace' => $alpha->id, 'location_id' => $alphaLocation->id, 'volunteered_at' => today()->toDateString(), 'hours' => 12])
            ->assertSessionHasNoErrors();
        $csv = $this->get(route('admin.reports.export', ['_admin_workspace' => $alpha->id]))->assertOk()->streamedContent();
        $this->assertStringContainsString('12.00', $csv);
        $this->assertStringNotContainsString('Private beta volunteer', $csv);
        $this->get(route('admin.users.index'))->assertSee('Remove from Alpha');
        $this->delete(route('admin.users.destroy', $existingAdmin), ['_admin_workspace' => $alpha->id, 'organization_id' => $beta->id])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('organization_user', ['organization_id' => $alpha->id, 'user_id' => $existingAdmin->id]);
        $this->assertDatabaseHas('organization_user', ['organization_id' => $beta->id, 'user_id' => $existingAdmin->id, 'role' => 'admin']);
        foreach (['entry.admin_created', 'entry.updated', 'membership.admin_invited', 'membership.removed', 'report.exported'] as $type) {
            $event = AuditEvent::where('event_type', $type)->where('actor_id', $admin->id)->where('organization_id', $alpha->id)->firstOrFail();
            $this->assertSame('platform_admin', $event->metadata['actor_role']);
        }
        $this->assertDatabaseMissing('organization_user', ['organization_id' => $alpha->id, 'user_id' => $admin->id]);
    }

    public function test_stale_forms_htmx_requests_and_exports_do_not_cross_workspaces(): void
    {
        $alpha = $this->organization('alpha');
        $beta = $this->organization('beta');
        $alphaLocation = $this->location($alpha);
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $this->actingAs($admin)->post(route('account.organizations.workspace', $alpha));
        $this->get(route('admin.locations.create'))->assertSee('name="_admin_workspace" value="'.$alpha->id.'"', false);
        $this->get(route('admin.dashboard'))->assertSee('X-Admin-Workspace', false);
        $this->post(route('account.organizations.workspace', $beta));
        $this->post(route('admin.locations.store'), ['_admin_workspace' => $alpha->id, 'name' => 'Stale location'])->assertStatus(409);
        $this->post(route('admin.locations.store'), ['name' => 'Unmarked location'])->assertStatus(409);
        $this->patch(route('admin.locations.toggle-active', $alphaLocation), [], ['HX-Request' => 'true', 'X-Admin-Workspace' => (string) $alpha->id])->assertStatus(409);
        $this->patch(route('admin.locations.toggle-active', $alphaLocation), ['_admin_workspace' => $beta->id], ['HX-Request' => 'true', 'X-Admin-Workspace' => (string) $alpha->id])->assertStatus(409);
        $this->get(route('admin.reports.export', ['_admin_workspace' => $alpha->id]))->assertStatus(409);
        $this->get(route('admin.dashboard', ['_admin_workspace' => $alpha->id, 'location_id' => $alphaLocation->id]))->assertStatus(409);
        $this->post(route('admin.locations.store'), ['_admin_workspace' => $beta->id, 'name' => 'Beta location'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('locations', ['organization_id' => $beta->id, 'name' => 'Beta location']);
        $this->assertDatabaseMissing('locations', ['name' => 'Stale location']);
        $this->post(route('account.platform'));
        $this->post(route('admin.locations.store'), ['_admin_workspace' => $beta->id, 'organization_id' => $alpha->id, 'name' => 'Stale platform location'])->assertStatus(409);
        $this->assertTrue($alphaLocation->fresh()->is_active);
    }

    public function test_workspace_supports_individual_hours_events_settings_and_htmx(): void
    {
        $organization = $this->organization('alpha');
        $location = $this->location($organization);
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $this->actingAs($admin)->post(route('account.organizations.workspace', $organization));
        $workspace = ['_admin_workspace' => $organization->id];

        $this->post(route('admin.special-events.store'), $workspace + [
            'name' => 'Cleanup', 'starts_on' => today()->toDateString(), 'is_active' => 1,
            'location_mode' => 'selected', 'location_ids' => [$location->id],
        ])->assertSessionHasNoErrors();
        $event = SpecialEvent::where('name', 'Cleanup')->firstOrFail();
        $this->post(route('admin.entries.store'), $workspace + [
            'name' => 'Named volunteer', 'location_id' => $location->id, 'special_event_id' => $event->id,
            'volunteered_at' => today()->toDateString(), 'hours' => 3,
        ])->assertSessionHasNoErrors();
        $entry = VolunteerEntry::where('name', 'Named volunteer')->firstOrFail();
        $this->patch(route('admin.entries.update-inline', $entry), ['field' => 'hours', 'value' => '4'],
            ['HX-Request' => 'true', 'X-Admin-Workspace' => (string) $organization->id])->assertOk()
            ->assertSee('name="_admin_workspace" value="'.$organization->id.'"', false);
        $this->assertSame('4.00', $entry->fresh()->hours);
        $this->get(route('admin.reports'))->assertOk()->assertSee('4.0');
        $this->get(route('account.hours'))->assertOk()->assertDontSee('Named volunteer');

        $this->put(route('admin.branding.update'), $workspace + [
            'name' => 'Updated Alpha', 'accent_color' => '#345678', 'welcome_title' => 'Welcome',
            'welcome_message' => 'Thank you', 'submit_button_text' => 'Submit',
            'success_message' => 'Thanks', 'label_name' => 'Name', 'label_location' => 'Location',
            'label_date' => 'Date', 'label_hours' => 'Hours',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Updated Alpha', $organization->fresh()->name);
        foreach (['event.created', 'entry.admin_created', 'entry.updated', 'organization.updated'] as $type) {
            $audit = AuditEvent::where('event_type', $type)->where('actor_id', $admin->id)->where('organization_id', $organization->id)->firstOrFail();
            $this->assertSame('platform_admin', $audit->metadata['actor_role']);
        }
        $this->get(route('admin.activity.index'))->assertOk()->assertSee('Platform admin');
        $this->delete(route('admin.entries.destroy', $entry), $workspace)->assertSessionHasNoErrors();
        $this->assertSoftDeleted($entry);
    }

    public function test_ordinary_admins_and_volunteers_cannot_enter_other_workspaces(): void
    {
        $alpha = $this->organization('alpha');
        $beta = $this->organization('beta');
        $betaLocation = $this->location($beta);
        $admin = User::factory()->create();
        $admin->organizations()->attach($alpha, ['role' => 'admin']);
        $volunteer = User::factory()->create();
        $volunteer->organizations()->attach($alpha, ['role' => 'volunteer']);

        $this->actingAs($admin)->post(route('account.organizations.workspace', $beta))->assertForbidden();
        $this->post(route('account.organizations.switch', $alpha));
        $this->get(route('admin.locations.edit', $betaLocation))->assertForbidden();
        $this->post(route('admin.organizations.administrators', $beta), ['name' => 'Other', 'email' => 'other@example.test'])->assertForbidden();
        $this->withSession(['admin_organization_id' => $beta->id])->get(route('admin.overview'))->assertForbidden();
        $this->actingAs($volunteer)->post(route('account.organizations.workspace', $beta))->assertForbidden();
        $this->withSession(['admin_organization_id' => $alpha->id])->get(route('admin.overview'))->assertForbidden();
    }

    public function test_archived_or_revoked_organizations_and_privileges_are_revalidated(): void
    {
        $organization = $this->organization('alpha');
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $this->actingAs($admin)->post(route('account.organizations.workspace', $organization));
        $organization->delete();
        $this->get(route('admin.overview'))->assertForbidden();
        $this->post(route('account.organizations.workspace', $organization))->assertNotFound();
        $organization->restore();
        $admin->update(['is_platform_admin' => false]);
        $this->get(route('admin.overview'))->assertForbidden();
        $this->post(route('account.organizations.workspace', $organization))->assertForbidden();
    }
}
