<?php

namespace App\Events;

use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Thunk\Verbs\Event;
use Thunk\Verbs\Facades\Verbs;

class WorkspaceMemberInvited extends Event
{
    public string $workspace_id;

    public string $email;

    public string $role;

    public string $inviter_id;

    public ?string $expires_at;

    public function handle()
    {
        // Create the invitation record
        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $this->workspace_id,
            'email' => strtolower($this->email),
            'role' => $this->role,
            'token' => Str::random(64),
            'inviter_id' => $this->inviter_id,
            'expires_at' => $this->expires_at ?: now()->addDays(7),
        ]);

        // Send the invitation email (only when not replaying)
        Verbs::unlessReplaying(function () use ($invitation) {
            Notification::route('mail', $invitation->email)
                ->notify(new WorkspaceInvitationNotification($invitation));
        });

        return $invitation;
    }

    public function fired()
    {
        // Log the invitation for debugging/auditing
        logger('Workspace member invited', [
            'workspace_id' => $this->workspace_id,
            'email' => $this->email,
            'role' => $this->role,
            'inviter_id' => $this->inviter_id,
        ]);
    }
}
