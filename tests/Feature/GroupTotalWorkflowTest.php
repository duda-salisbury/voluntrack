<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Location;
use App\Models\Organization;
use App\Models\SpecialEvent;
use App\Models\User;
use App\Models\VolunteerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GroupTotalWorkflowTest extends TestCase
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

    private function location(Organization $organization, string $slug = 'forest'): Location
    {
        return Location::create(['organization_id' => $organization->id, 'name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function admin(Organization $organization): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'admin']);
        return $user;
    }

    private function useOrganization(User $admin, Organization $organization): void
    {
        $this->actingAs($admin)->post(route('account.organizations.switch', $organization))->assertRedirect(route('admin.overview'));
    }

    private function payload(Location $location, array $extra = []): array
    {
        return array_merge(['location_id' => $location->id, 'volunteered_at' => '2026-10-01', 'hours' => 36], $extra);
    }

    public function test_admin_records_unattributed_totals_and_event_defaults_without_creating_people(): void
    {
        $org = $this->organization('alpha');
        $location = $this->location($org);
        $admin = $this->admin($org);
        $event = SpecialEvent::create(['organization_id' => $org->id, 'name' => 'Trail day', 'starts_on' => '2026-10-01', 'is_active' => true]);
        $event->locations()->attach($location);
        $this->useOrganization($admin, $org);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Add individual hours')->assertSee('Add multiple volunteers')->assertSee('Add group total');
        $form = $this->get(route('admin.entries.group-total', ['event' => $event->id]))->assertOk()
            ->assertSee("Only record hours that haven't already been submitted individually.", false)
            ->assertSee('value="2026-10-01"', false);
        $this->assertMatchesRegularExpression('/<option value="'.$location->id.'" selected>/', $form->getContent());
        $this->post(route('admin.entries.group-total.store'), $this->payload($location, [
            'special_event_id' => $event->id, 'participant_count' => 12, 'is_matched' => 1,
            'name' => 'Fake name', 'volunteer_user_id' => $admin->id, 'anon_token' => 'spoofed',
            'created_by_admin_id' => 999, 'organization_id' => 999, 'is_group_total' => 0,
        ]))->assertSessionHasNoErrors();
        $entry = VolunteerEntry::firstOrFail();
        $this->assertSame($org->id, $entry->organization_id);
        $this->assertTrue($entry->is_group_total);
        $this->assertSame($admin->id, $entry->created_by_admin_id);
        $this->assertSame(12, $entry->participant_count);
        $this->assertTrue($entry->is_matched);
        $this->assertSame($admin->id, $entry->matched_by_admin_id);
        $this->assertNull($entry->name);
        $this->assertNull($entry->volunteer_user_id);
        $this->assertNull($entry->anon_token);
        $this->assertDatabaseCount('users', 1);
        $this->post(route('admin.entries.group-total.store'), $this->payload($location, ['hours' => '99999.99']))->assertSessionHasNoErrors();
        $this->assertNull(VolunteerEntry::orderByDesc('id')->firstOrFail()->participant_count);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Group total')->assertSee('12 reported participants')->assertSee('Entered by '.$admin->name);
    }

    public function test_group_total_validation_and_organization_authorization(): void
    {
        $org = $this->organization('alpha');
        $other = $this->organization('beta');
        $location = $this->location($org);
        $foreign = $this->location($other);
        $foreignEvent = SpecialEvent::create(['organization_id' => $other->id, 'name' => 'Other event', 'starts_on' => '2026-10-01', 'is_active' => true]);
        $admin = $this->admin($org);
        $this->useOrganization($admin, $org);
        foreach (['0', '-1', '100000', '0.001', '1e2'] as $value) {
            $this->post(route('admin.entries.group-total.store'), $this->payload($location, ['hours' => $value]))->assertSessionHasErrors('hours');
        }
        foreach ([0, -1, 100001, 1.5] as $count) {
            $this->post(route('admin.entries.group-total.store'), $this->payload($location, ['participant_count' => $count]))->assertSessionHasErrors('participant_count');
        }
        $this->post(route('admin.entries.group-total.store'), $this->payload($foreign))->assertSessionHasErrors('location_id');
        $this->post(route('admin.entries.group-total.store'), $this->payload($location, ['special_event_id' => $foreignEvent->id]))->assertSessionHasErrors('special_event_id');
        $this->assertDatabaseCount('volunteer_entries', 0);
        $volunteer = User::factory()->create();
        $volunteer->organizations()->attach($org, ['role' => 'volunteer']);
        $this->actingAs($volunteer)->withSession(['admin_organization_id' => $org->id])
            ->post(route('admin.entries.group-total.store'), $this->payload($location))->assertForbidden();
    }

    public function test_group_totals_can_be_edited_and_deleted_without_conversion_or_cross_tenant_access(): void
    {
        $org = $this->organization('alpha');
        $other = $this->organization('beta');
        $location = $this->location($org);
        $override = $this->location($org, 'meadow');
        $admin = $this->admin($org);
        $event = SpecialEvent::create(['organization_id' => $org->id, 'name' => 'Trail day', 'starts_on' => '2026-10-01', 'is_active' => true]);
        $event->locations()->attach($location);
        $this->useOrganization($admin, $org);
        $this->post(route('admin.entries.group-total.store'), $this->payload($location, ['participant_count' => 12]))->assertSessionHasNoErrors();
        $entry = VolunteerEntry::firstOrFail();
        $this->get(route('admin.entries.edit', $entry))->assertOk()->assertSee('Edit group total')->assertDontSee('Volunteer Name');
        $this->put(route('admin.entries.update', $entry), $this->payload($override, [
            'hours' => 48, 'participant_count' => 16, 'special_event_id' => $event->id,
            'volunteer_user_id' => $admin->id, 'name' => 'Spoofed', 'is_group_total' => 0, 'is_matched' => 1,
        ]))->assertSessionHasNoErrors();
        $entry->refresh();
        $this->assertTrue($entry->is_group_total);
        $this->assertNull($entry->volunteer_user_id);
        $this->assertNull($entry->name);
        $this->assertSame($override->id, $entry->location_id);
        $this->assertSame(16, $entry->participant_count);
        $this->assertTrue($entry->is_matched);
        $this->patchJson(route('admin.entries.update-inline', $entry), ['field' => 'hours', 'value' => '120.25'])->assertOk();
        $this->patchJson(route('admin.entries.update-inline', $entry), ['field' => 'hours', 'value' => '100000'])->assertUnprocessable();
        $this->assertSame('120.25', $entry->fresh()->hours);
        $otherAdmin = $this->admin($other);
        $this->useOrganization($otherAdmin, $other);
        $this->get(route('admin.entries.edit', $entry))->assertForbidden();
        $this->put(route('admin.entries.update', $entry), $this->payload($location))->assertForbidden();
        $this->delete(route('admin.entries.destroy', $entry))->assertForbidden();
        $this->useOrganization($admin, $org);
        $this->delete(route('admin.entries.destroy', $entry))->assertSessionHasNoErrors();
        $this->assertSoftDeleted('volunteer_entries', ['id' => $entry->id]);
    }

    public function test_reports_exports_and_histories_distinguish_group_hours_and_participations(): void
    {
        $org = $this->organization('alpha');
        $other = $this->organization('beta');
        $location = $this->location($org);
        $admin = $this->admin($org);
        $volunteer = User::factory()->create(['name' => 'Named Volunteer']);
        $volunteer->organizations()->attach($org, ['role' => 'volunteer']);
        $event = SpecialEvent::create(['organization_id' => $org->id, 'name' => 'Trail day', 'starts_on' => '2026-10-01', 'is_active' => true]);
        VolunteerEntry::create(['organization_id' => $org->id, 'location_id' => $location->id, 'volunteered_at' => '2026-10-01', 'hours' => 5, 'name' => $volunteer->name, 'volunteer_user_id' => $volunteer->id, 'special_event_id' => $event->id, 'source' => 'manual']);
        VolunteerEntry::create(['organization_id' => $org->id, 'location_id' => $location->id, 'volunteered_at' => '2026-10-01', 'hours' => 3, 'source' => 'manual']);
        $this->useOrganization($admin, $org);
        $this->post(route('admin.entries.group-total.store'), $this->payload($location, ['participant_count' => 12, 'special_event_id' => $event->id, 'is_matched' => 1, 'notes' => '=danger']))->assertSessionHasNoErrors();
        $group = VolunteerEntry::where('is_group_total', true)->firstOrFail();
        $this->post(route('admin.entries.group-total.store'), $this->payload($location, ['hours' => 4]))->assertSessionHasNoErrors();
        $this->post(route('admin.entries.group-total.store'), $this->payload($location, ['hours' => 6, 'participant_count' => 5, 'volunteered_at' => '2026-09-01']))->assertSessionHasNoErrors();
        VolunteerEntry::create(['organization_id' => $other->id, 'location_id' => $this->location($other)->id, 'volunteered_at' => '2026-10-01', 'hours' => 150, 'source' => 'admin', 'is_group_total' => true, 'participant_count' => 50]);

        $this->get(route('admin.reports', ['date_from' => '2026-10-01', 'date_to' => '2026-10-01']))->assertOk()
            ->assertSee('12 reported participations')->assertSee('40.0 group-total hours')
            ->assertViewHas('stats', fn ($stats) => $stats['total_hours'] === 48.0 && $stats['individual_hours'] === 8.0
                && $stats['group_hours'] === 40.0 && $stats['matched_hours'] === 36.0 && $stats['event_hours'] === 41.0
                && $stats['estimated_volunteers'] === 1 && $stats['unidentified_entries'] === 1 && $stats['reported_participations'] === 12)
            ->assertViewHas('byLocation', fn ($rows) => (float) $rows->sum('total') === 48.0)
            ->assertViewHas('byEvent', fn ($rows) => (float) $rows->sum('total') === 48.0)
            ->assertViewHas('byMonth', fn ($rows) => $rows->sum() === 48.0);
        $this->get(route('admin.reports', ['special_event_id' => $event->id, 'matched' => 'yes']))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total_hours'] === 36.0 && $stats['reported_participations'] === 12);
        $this->get(route('admin.volunteer-activity'))->assertOk()->assertViewHas('unlinked', 1);
        $csv = $this->get(route('admin.reports.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Contribution type","Reported participants","Entered by"', $csv);
        $this->assertStringContainsString('Group total', $csv);
        $this->assertStringContainsString("'=danger", $csv);
        $this->assertStringNotContainsString('150.00', $csv);
        $rows = array_map(fn ($line) => str_getcsv($line, ',', '"', ''), explode("\n", trim($csv)));
        $headers = array_shift($rows);
        $reported = array_search('Reported participants', $headers, true);
        $hours = array_search('Hours', $headers, true);
        $type = array_search('Contribution type', $headers, true);
        $row = collect($rows)->first(fn ($row) => $row[$reported] === '12');
        $this->assertSame('36.00', $row[$hours]);
        $this->assertSame('Group total', $row[$type]);
        $this->actingAs($volunteer)->get(route('account.hours'))->assertOk()->assertViewHas('totalHours', fn ($hours) => (float) $hours === 5.0);

        DB::table('volunteer_entries')->where('id', $group->id)->update(['volunteer_user_id' => $volunteer->id]);
        $this->get(route('org.account.history', $org))->assertOk()->assertViewHas('totalHours', fn ($hours) => (float) $hours === 5.0);
    }

    public function test_group_total_changes_are_audited_without_logging_notes(): void
    {
        $org = $this->organization('alpha');
        $location = $this->location($org);
        $admin = $this->admin($org);
        $this->useOrganization($admin, $org);
        $this->post(route('admin.entries.group-total.store'), $this->payload($location, ['notes' => 'Private group note']))->assertSessionHasNoErrors();
        $entry = VolunteerEntry::firstOrFail();
        $this->put(route('admin.entries.update', $entry), $this->payload($location, ['hours' => 40, 'participant_count' => 12]))->assertSessionHasNoErrors();
        $this->delete(route('admin.entries.destroy', $entry))->assertSessionHasNoErrors();
        foreach (['entry.admin_created', 'entry.updated', 'entry.deleted'] as $type) {
            $this->assertDatabaseHas('audit_events', ['event_type' => $type, 'actor_id' => $admin->id, 'organization_id' => $org->id, 'subject_id' => $entry->id]);
        }
        $this->assertDatabaseHas('audit_events', ['event_type' => 'entry.deleted', 'description' => 'Deleted a group total']);
        $this->assertStringNotContainsString('Private group note', AuditEvent::all()->toJson());
    }

    public function test_migration_preserves_existing_entries_and_refuses_a_destructive_rollback(): void
    {
        $migration = require database_path('migrations/2026_11_15_000005_add_group_totals_to_volunteer_entries.php');
        $migration->down();
        $org = $this->organization('alpha');
        $location = $this->location($org);
        $id = DB::table('volunteer_entries')->insertGetId([
            'organization_id' => $org->id, 'location_id' => $location->id,
            'name' => 'Existing volunteer', 'hours' => 4.5, 'volunteered_at' => '2026-10-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $migration->up();
        $this->assertDatabaseHas('volunteer_entries', ['id' => $id, 'name' => 'Existing volunteer', 'hours' => 4.5, 'is_group_total' => false]);
        DB::table('volunteer_entries')->where('id', $id)->update(['is_group_total' => true, 'participant_count' => 12]);
        try {
            $migration->down();
            $this->fail('Rollback should not discard group-total data.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('group total records exist', $exception->getMessage());
        }
        $this->assertDatabaseHas('volunteer_entries', ['id' => $id, 'is_group_total' => true, 'participant_count' => 12]);
    }
}
