<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationAccessGranted extends Notification
{
    use Queueable;

    public function __construct(public readonly Organization $organization) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Organization access on Voluntrack')
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been invited to administer {$this->organization->name}.")
            ->line('Use your existing Voluntrack credentials to sign in.')
            ->action('Sign in', route('org.account.login', $this->organization));
    }
}
