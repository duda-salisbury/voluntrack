<?php

namespace App\Notifications;

use App\Models\OrganizationRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationRequestSubmitted extends Notification
{
    public function __construct(public readonly OrganizationRequest $organizationRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New organization request on Voluntrack')
            ->line('A new organization or administrator access request is ready for review.')
            ->action('Review request', route('admin.organization-requests.show', $this->organizationRequest));
    }
}
