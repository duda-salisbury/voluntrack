<?php

namespace App\Observers;

use App\Models\Location;
use App\Models\Organization;
use App\Models\SpecialEvent;
use App\Models\VolunteerEntry;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditObserver
{
    public function created(Model $model): void
    {
        if ($model instanceof VolunteerEntry) {
            $admin = $model->source === 'admin';
            $description = ($admin ? 'Administrator recorded volunteer hours' : 'Volunteer hours submitted')
                .' — '.number_format((float) $model->hours, 2, '.', '').' hours';
            $this->record($model, $admin ? 'entry.admin_created' : 'entry.submitted', 'hours', $description,
                $model->volunteer_user_id, ['hours' => (float) $model->hours]);
        } elseif ($model instanceof Organization) {
            $this->record($model, 'organization.created', 'organization', 'Created an organization');
        } elseif ($model instanceof Location) {
            $this->record($model, 'location.created', 'location', 'Created '.Str::limit($model->name, 120).' location');
        } elseif ($model instanceof SpecialEvent) {
            $this->record($model, 'event.created', 'event', 'Created '.Str::limit($model->name, 120).' event');
        }
    }

    public function updated(Model $model): void
    {
        if (! $model->wasChanged()) {
            return;
        }
        $fields = $this->changedFields($model);
        $metadata = $fields ? ['changes' => $fields] : null;
        if ($model instanceof VolunteerEntry) {
            $this->record($model, 'entry.updated', 'hours', 'Updated a volunteer hour entry', $model->volunteer_user_id, $metadata);
        } elseif ($model instanceof Organization) {
            if ($model->wasChanged('is_active')) {
                $this->record($model, $model->is_active ? 'organization.activated' : 'organization.deactivated',
                    'organization', $model->is_active ? 'Activated the organization' : 'Deactivated the organization');
            }
            if ($model->wasChanged(['name', 'slug', 'logo_path', 'accent_color', 'settings'])) {
                $this->record($model, 'organization.updated', 'organization', 'Updated organization settings', metadata: $metadata);
            }
        } elseif ($model instanceof Location) {
            $this->record($model, 'location.updated', 'location', 'Updated '.Str::limit($model->name, 120).' location', metadata: $metadata);
        } elseif ($model instanceof SpecialEvent) {
            $this->record($model, 'event.updated', 'event', 'Updated '.Str::limit($model->name, 120).' event', metadata: $metadata);
        }
    }

    private function changedFields(Model $model): array
    {
        $labels = match (true) {
            $model instanceof VolunteerEntry => [
                'hours' => 'Hours', 'volunteered_at' => 'Date', 'location_id' => 'Location',
                'special_event_id' => 'Event', 'name' => 'Volunteer name', 'notes' => 'Notes',
                'is_matched' => 'Match status', 'volunteer_user_id' => 'Linked account',
            ],
            $model instanceof Organization => [
                'name' => 'Name', 'slug' => 'Public URL', 'logo_path' => 'Logo', 'accent_color' => 'Brand color', 'settings' => 'Form settings',
            ],
            $model instanceof Location => [
                'name' => 'Name', 'slug' => 'URL', 'description' => 'Description', 'is_active' => 'Availability',
                'organization_id' => 'Organization', 'source' => 'Source', 'external_id' => 'External reference',
            ],
            $model instanceof SpecialEvent => [
                'name' => 'Name', 'description' => 'Description', 'starts_on' => 'Start date',
                'ends_on' => 'End date', 'is_active' => 'Availability',
            ],
            default => [],
        };

        return array_values(array_intersect_key($labels, $model->getChanges()));
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof VolunteerEntry) {
            $this->record($model, 'entry.deleted', 'hours', 'Deleted a volunteer hour entry', $model->volunteer_user_id);
        } elseif ($model instanceof Organization) {
            $this->record($model, 'organization.archived', 'organization', 'Archived the organization');
        } elseif ($model instanceof Location) {
            $this->record($model, 'location.deleted', 'location', 'Deleted '.Str::limit($model->name, 120).' location');
        } elseif ($model instanceof SpecialEvent) {
            $this->record($model, 'event.deleted', 'event', 'Deleted '.Str::limit($model->name, 120).' event');
        }
    }

    private function record(Model $model, string $type, string $category, string $description, ?int $subjectUserId = null, ?array $metadata = null): void
    {
        Audit::record($type, $category, $description, actorId: auth()->id(),
            organizationId: $model instanceof Organization ? $model->id : $model->organization_id,
            subjectUserId: $subjectUserId, subjectType: $category, subjectId: $model->id, metadata: $metadata);
    }
}
