<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Typed accessor for the Organization.settings JSON column.
 *
 * Values are display-only and never queried, so they live in JSON rather than
 * individual columns.  Adding a new label or toggle only requires updating this
 * class and the branding form — no migration needed.
 *
 * Usage:  $org->config->welcomeTitle
 *         $org->config->label('location')
 */
class OrganizationSettings implements Arrayable
{
    public function __construct(
        public readonly string $welcomeTitle,
        public readonly string $welcomeMessage,
        public readonly string $submitButtonText,
        public readonly string $successMessage,
        // Field labels
        public readonly string $labelName,
        public readonly string $labelLocation,
        public readonly string $labelDate,
        public readonly string $labelHours,
        // Form behaviour
        public readonly bool $requireName,
        public readonly bool $showNotes,
        public readonly bool $showHoursHint,
    ) {}

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    public static function defaults(): self
    {
        return new self(
            welcomeTitle: 'Log Your Volunteer Hours',
            welcomeMessage: 'Track your volunteer trail work and conservation efforts.',
            submitButtonText: 'Submit Hours',
            successMessage: 'Thank you! Your volunteer hours have been successfully recorded.',
            labelName: 'Your Name',
            labelLocation: 'Location',
            labelDate: 'Date',
            labelHours: 'Hours',
            requireName: false,
            showNotes: false,
            showHoursHint: true,
        );
    }

    public static function fromArray(array $data): self
    {
        $d = self::defaults();
        $labels = $data['labels'] ?? [];
        $form   = $data['form']   ?? [];

        return new self(
            welcomeTitle:     $data['welcome_title']      ?? $d->welcomeTitle,
            welcomeMessage:   $data['welcome_message']    ?? $d->welcomeMessage,
            submitButtonText: $data['submit_button_text'] ?? $d->submitButtonText,
            successMessage:   $data['success_message']    ?? $d->successMessage,
            labelName:        $labels['name']             ?? $d->labelName,
            labelLocation:    $labels['location']         ?? $d->labelLocation,
            labelDate:        $labels['date']             ?? $d->labelDate,
            labelHours:       $labels['hours']            ?? $d->labelHours,
            requireName:      (bool) ($form['require_name']    ?? $d->requireName),
            showNotes:        (bool) ($form['show_notes']      ?? $d->showNotes),
            showHoursHint:    (bool) ($form['show_hours_hint'] ?? $d->showHoursHint),
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Retrieve any label by its key with a safe fallback. */
    public function label(string $key): string
    {
        return match ($key) {
            'name'     => $this->labelName,
            'location' => $this->labelLocation,
            'date'     => $this->labelDate,
            'hours'    => $this->labelHours,
            default    => ucfirst($key),
        };
    }

    // -------------------------------------------------------------------------
    // Serialisation
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'welcome_title'      => $this->welcomeTitle,
            'welcome_message'    => $this->welcomeMessage,
            'submit_button_text' => $this->submitButtonText,
            'success_message'    => $this->successMessage,
            'labels' => [
                'name'     => $this->labelName,
                'location' => $this->labelLocation,
                'date'     => $this->labelDate,
                'hours'    => $this->labelHours,
            ],
            'form' => [
                'require_name'    => $this->requireName,
                'show_notes'      => $this->showNotes,
                'show_hours_hint' => $this->showHoursHint,
            ],
        ];
    }
}
