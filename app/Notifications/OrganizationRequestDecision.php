<?php

namespace App\Notifications;

use App\Models\OrganizationRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationRequestDecision extends Notification
{
    public function __construct(public readonly OrganizationRequest $organizationRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->organizationRequest;
        $mail = (new MailMessage)
            ->subject('Your Voluntrack organization request')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your request for {$request->organization_name} has been {$request->status}.");
        if ($request->status === 'declined' && $request->decline_reason) {
            $mail->line('Reason: '.$request->decline_reason);
        }
        if ($request->status === 'approved') {
            $mail->line('Sign in to open your organization workspace and complete setup.');
        }

        return $mail->action('View your request', route('organization-requests.index'));
    }
}
