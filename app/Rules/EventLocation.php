<?php

namespace App\Rules;

use App\Models\SpecialEvent;
use App\Models\VolunteerEntry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EventLocation implements ValidationRule
{
    public function __construct(
        private readonly mixed $eventId,
        private readonly int $organizationId,
        private readonly ?VolunteerEntry $entry = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($this->eventId) || ! ctype_digit((string) $this->eventId) || ! $value) {
            return;
        }

        if ($this->entry && (string) $this->entry->special_event_id === (string) $this->eventId
            && (string) $this->entry->location_id === (string) $value) {
            return;
        }

        $event = SpecialEvent::where('organization_id', $this->organizationId)->find($this->eventId);
        if ($event && $event->locations()->exists() && ! $event->locations()->where('locations.id', $value)->exists()) {
            $fail('Choose a location assigned to this special event.');
        }
    }
}
