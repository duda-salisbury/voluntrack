<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AdministratorInvitation extends ResetPassword
{
    public function __construct(string $token, public readonly string $organizationName)
    {
        parent::__construct($token);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Manage {$this->organizationName} on Voluntrack")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been invited to administer {$this->organizationName}.")
            ->line('Choose your own password to manage volunteer hours, locations, events, and your public form.')
            ->action('Set your password', $this->resetUrl($notifiable))
            ->line('This link expires in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
            ->line('If you were not expecting this invitation, contact your platform administrator.');
    }
}
