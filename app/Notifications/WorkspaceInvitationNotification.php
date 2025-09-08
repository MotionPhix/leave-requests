<?php

namespace App\Notifications;

use App\Models\WorkspaceInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class WorkspaceInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WorkspaceInvitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'invitations.accept',
            Carbon::now()->addDays(7),
            [
                'workspace' => $this->invitation->workspace_id,
                'token' => $this->invitation->token,
            ]
        );

        return (new MailMessage)
            ->subject('You\'re invited to join a workspace')
            ->greeting('Hello!')
            ->line("You've been invited to join {$this->invitation->workspace->name} as {$this->invitation->role}.")
            ->action('Accept Invitation', $url)
            ->line('If you did not expect this, you can ignore this email.');
    }
}
