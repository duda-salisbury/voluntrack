<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationRequest;
use App\Models\User;
use App\Notifications\OrganizationRequestDecision;
use App\Notifications\OrganizationRequestSubmitted;
use Illuminate\Database\QueryException;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrganizationRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
    }

    private function details(array $overrides = []): array
    {
        return array_merge([
            'type' => 'new',
            'organization_name' => 'Forest Friends',
            'website' => 'https://www.forest.test/about',
            'contact_name' => 'A Volunteer',
            'contact_email' => 'volunteer@example.test',
            'description' => 'Trail restoration',
        ], $overrides);
    }

    private function org(string $name, string $slug): Organization
    {
        return Organization::create(['name' => $name, 'slug' => $slug, 'is_active' => true, 'accent_color' => '#245744']);
    }

    public function test_guest_can_register_and_submit_without_losing_the_draft(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $this->get(route('home'))->assertOk()->assertSee('Get Started');
        $this->post(route('organization-requests.store'), $this->details())
            ->assertRedirect(route('organization-requests.authenticate'));
        $this->assertDatabaseCount('organization_requests', 0);
        $this->get(route('organization-requests.register'))->assertOk()->assertSee('volunteer@example.test');
        $this->post(route('organization-requests.register.store'), [
            'name' => 'A Volunteer', 'email' => 'volunteer@example.test',
            'password' => 'password-123', 'password_confirmation' => 'password-123',
        ])->assertRedirect(route('organization-requests.create'));
        $this->get(route('organization-requests.create'))->assertOk()->assertSee('Forest Friends')->assertSee('Trail restoration');
        $this->post(route('organization-requests.store'), $this->details())->assertRedirect(route('organization-requests.index'));
        $this->assertDatabaseHas('organization_requests', ['organization_name' => 'Forest Friends', 'status' => 'pending', 'requester_id' => auth()->id()]);
        $this->assertDatabaseCount('organization_user', 0);
        $this->get(route('organization-requests.index'))->assertOk()->assertSee('awaiting review');
        Notification::assertSentTo($admin, OrganizationRequestSubmitted::class);
    }

    public function test_existing_login_returns_to_request_with_draft_intact(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password-123')]);
        $this->post(route('organization-requests.store'), $this->details())->assertRedirect();
        $this->get(route('organization-requests.authenticate'))->assertOk();
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password-123'])
            ->assertRedirect(route('organization-requests.create'));
        $this->get(route('organization-requests.create'))->assertOk()->assertSee('Trail restoration');
    }

    public function test_superadmin_notification_link_returns_to_review_after_sign_in(): void
    {
        $platform = User::factory()->create(['is_platform_admin' => true, 'password' => Hash::make('password-123')]);
        $applicant = User::factory()->create();
        $this->actingAs($applicant)->post(route('organization-requests.store'), $this->details())->assertSessionHasNoErrors();
        $item = OrganizationRequest::firstOrFail();
        $this->post(route('logout'));
        $this->get(route('admin.organization-requests.show', $item))->assertRedirect(route('login'));
        $this->post(route('login.post'), ['email' => $platform->email, 'password' => 'password-123'])
            ->assertRedirect(route('admin.organization-requests.show', $item));
        $this->get(route('admin.organization-requests.show', $item))->assertOk();
    }

    public function test_duplicate_pending_requests_are_prevented_by_name_domain_and_database_constraint(): void
    {
        User::factory()->create(['is_platform_admin' => true]);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('organization-requests.store'), $this->details())->assertSessionHasNoErrors();
        $this->post(route('organization-requests.store'), $this->details(['organization_name' => '  FOREST   FRIENDS  ', 'website' => null]))
            ->assertSessionHasErrors('organization_name');
        $this->post(route('organization-requests.store'), $this->details(['organization_name' => 'Another name', 'website' => 'https://forest.test']))
            ->assertSessionHasErrors('organization_name');
        $this->assertDatabaseCount('organization_requests', 1);
        $copy = OrganizationRequest::firstOrFail()->replicate();
        $this->expectException(QueryException::class);
        $copy->save();
    }

    public function test_only_platform_admins_review_and_applicants_see_only_their_own_requests(): void
    {
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $orgAdmin = User::factory()->create();
        $org = $this->org('Existing Group', 'existing-group');
        $orgAdmin->organizations()->attach($org, ['role' => 'admin']);
        $this->actingAs($owner)->post(route('organization-requests.store'), $this->details())->assertSessionHasNoErrors();
        $item = OrganizationRequest::firstOrFail();
        $this->actingAs($other)->get(route('organization-requests.index'))->assertOk()->assertDontSee('Forest Friends');
        $this->actingAs($orgAdmin)->withSession(['admin_organization_id' => $org->id])
            ->get(route('admin.organization-requests.show', $item))->assertForbidden();
        $this->post(route('admin.organization-requests.decide', $item), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($platform)->withSession(['admin_organization_id' => null]);
        $this->post(route('account.platform'));
        $this->get(route('admin.organization-requests.index'))->assertOk()->assertSee('Forest Friends');
        $this->get(route('admin.organization-requests.show', $item))->assertOk()->assertSee('Trail restoration');
        $this->get(route('admin.overview'))->assertOk()->assertSee('1 organization request');
    }

    public function test_approval_is_atomic_idempotent_and_preserves_existing_memberships(): void
    {
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $applicant = User::factory()->create();
        $other = $this->org('Other Group', 'other-group');
        $applicant->organizations()->attach($other, ['role' => 'volunteer']);
        $this->actingAs($applicant)->post(route('organization-requests.store'), $this->details())->assertSessionHasNoErrors();
        $item = OrganizationRequest::firstOrFail();
        $this->actingAs($platform)->post(route('admin.organization-requests.decide', $item), ['decision' => 'approved', 'review_notes' => 'Private note'])->assertSessionHasNoErrors();
        $item->refresh();
        $this->assertSame('approved', $item->status);
        $this->assertTrue($applicant->fresh()->hasOrganizationRole($item->organization_id, 'admin'));
        $this->assertTrue($applicant->fresh()->hasOrganizationRole($other->id, 'volunteer'));
        $this->assertSame('forest-friends', $item->organization->slug);
        Notification::assertSentTo($applicant, OrganizationRequestDecision::class);
        $this->post(route('admin.organization-requests.decide', $item), ['decision' => 'approved'])->assertSessionHasErrors('decision');
        $this->assertDatabaseCount('organizations', 2);
        $this->actingAs($applicant)->get(route('organization-requests.index'))->assertOk()->assertSee('Open organization setup')->assertDontSee('Private note');
        $this->post(route('account.organizations.switch', $item->organization))->assertRedirect(route('admin.overview'));
        $this->get(route('admin.overview'))->assertOk()->assertSee('Review organization details')->assertSee('Add a location')->assertSee('Share your form');
    }

    public function test_matching_requests_are_flagged_and_cannot_create_duplicate_organizations(): void
    {
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($first)->post(route('organization-requests.store'), $this->details())->assertSessionHasNoErrors();
        $this->actingAs($second)->post(route('organization-requests.store'), $this->details(['organization_name' => 'Different name', 'website' => 'https://forest.test']))->assertSessionHasNoErrors();
        $items = OrganizationRequest::orderBy('id')->get();
        $this->actingAs($platform)->get(route('admin.organization-requests.show', $items[0]))->assertOk()->assertSee('Other pending requests')->assertSee('Different name');
        $this->post(route('admin.organization-requests.decide', $items[0]), ['decision' => 'approved'])->assertSessionHasNoErrors();
        $this->get(route('admin.organization-requests.show', $items[1]))->assertOk()->assertSee('Existing organizations')->assertSee('Forest Friends');
        $this->post(route('admin.organization-requests.decide', $items[1]), ['decision' => 'approved'])->assertSessionHasErrors('decision');
        $this->assertSame('pending', $items[1]->fresh()->status);
        $this->assertDatabaseCount('organizations', 1);
        $this->assertFalse($second->fresh()->hasOrganizationRole($items[0]->fresh()->organization_id));
    }

    public function test_failed_provisioning_rolls_back_organization_and_membership(): void
    {
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $applicant = User::factory()->create();
        $this->actingAs($applicant)->post(route('organization-requests.store'), $this->details())->assertSessionHasNoErrors();
        $item = OrganizationRequest::firstOrFail();
        Organization::created(fn () => throw new \RuntimeException('Provisioning failed'));
        try {
            $this->actingAs($platform)->withoutExceptionHandling()->post(route('admin.organization-requests.decide', $item), ['decision' => 'approved']);
            $this->fail('Provisioning should fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Provisioning failed', $exception->getMessage());
        } finally {
            Organization::flushEventListeners();
        }
        $this->assertSame('pending', $item->fresh()->status);
        $this->assertDatabaseCount('organizations', 0);
        $this->assertDatabaseCount('organization_user', 0);
    }

    public function test_an_archived_matching_organization_blocks_new_provisioning(): void
    {
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $applicant = User::factory()->create();
        $org = $this->org('Forest Friends', 'forest-friends');
        $org->delete();
        $this->actingAs($applicant)->post(route('organization-requests.store'), $this->details(['organization_name' => 'FOREST FRIENDS', 'website' => null]))->assertSessionHasNoErrors();
        $item = OrganizationRequest::firstOrFail();
        $this->actingAs($platform)->get(route('admin.organization-requests.show', $item))->assertOk()->assertSee('Forest Friends (archived)');
        $this->post(route('admin.organization-requests.decide', $item), ['decision' => 'approved'])->assertSessionHasErrors('decision');
        $this->assertSame('pending', $item->fresh()->status);
        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount('organization_user', 0);
    }

    public function test_notification_failures_are_reported_without_undoing_requests_or_decisions(): void
    {
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $applicant = User::factory()->create();
        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('send')->twice()->andThrow(new \RuntimeException('Mail unavailable'));
        $this->app->instance(Dispatcher::class, $dispatcher);
        $this->actingAs($applicant)->post(route('organization-requests.store'), $this->details())->assertSessionHas('error');
        $item = OrganizationRequest::firstOrFail();
        $this->actingAs($platform)->post(route('admin.organization-requests.decide', $item), ['decision' => 'approved'])->assertSessionHas('error');
        $this->assertSame('approved', $item->fresh()->status);
        $this->assertTrue($applicant->fresh()->hasOrganizationRole($item->fresh()->organization_id, 'admin'));
        $this->get(route('admin.organization-requests.show', $item))->assertOk()->assertSee('Resend decision email');
    }

    public function test_existing_organization_access_requires_explicit_verification_and_does_not_create_an_organization(): void
    {
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $applicant = User::factory()->create();
        $org = $this->org('Forest Friends', 'forest-friends');
        $other = $this->org('Another Group', 'another-group');
        $applicant->organizations()->attach($org, ['role' => 'volunteer']);
        $applicant->organizations()->attach($other, ['role' => 'admin']);
        $this->actingAs($applicant)->post(route('organization-requests.store'), $this->details(['type' => 'existing', 'existing_organization_id' => $other->id]))->assertSessionHasNoErrors();
        $item = OrganizationRequest::firstOrFail();
        $this->assertSame($org->id, $item->existing_organization_id);
        $this->assertTrue($applicant->hasOrganizationRole($org->id, 'volunteer'));
        $this->actingAs($platform)->post(route('admin.organization-requests.decide', $item), ['decision' => 'approved'])->assertSessionHasErrors('verified');
        $this->assertTrue($applicant->fresh()->hasOrganizationRole($org->id, 'volunteer'));
        $this->post(route('admin.organization-requests.decide', $item), ['decision' => 'approved', 'verified' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($applicant->fresh()->hasOrganizationRole($org->id, 'admin'));
        $this->assertTrue($applicant->fresh()->hasOrganizationRole($other->id, 'admin'));
        $this->assertDatabaseCount('organizations', 2);
    }

    public function test_decline_shares_reason_but_not_private_notes_and_preserves_account(): void
    {
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $applicant = User::factory()->create();
        $this->actingAs($applicant)->post(route('organization-requests.store'), $this->details())->assertSessionHasNoErrors();
        $item = OrganizationRequest::firstOrFail();
        $this->actingAs($platform)->post(route('admin.organization-requests.decide', $item), [
            'decision' => 'declined', 'review_notes' => 'Private review', 'decline_reason' => 'Please contact your organization.',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $applicant->id, 'deleted_at' => null]);
        $this->assertDatabaseCount('organizations', 0);
        $this->actingAs($applicant)->get(route('organization-requests.index'))->assertOk()->assertSee('Please contact your organization.')->assertDontSee('Private review');
        Notification::assertSentTo($applicant, OrganizationRequestDecision::class, function ($notification) use ($applicant) {
            $mail = $notification->toMail($applicant);
            return str_contains(implode(' ', $mail->introLines), 'Please contact your organization.')
                && ! str_contains(implode(' ', $mail->introLines), 'Private review');
        });
    }
}
