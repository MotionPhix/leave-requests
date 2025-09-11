<?php

namespace App\Events;

use App\Models\WorkspaceInvitation;
use Thunk\Verbs\Event;

class WorkspaceInvitationCancelled extends Event
{
    public string $workspace_id;

    public int $invitation_id;

    public string $cancelled_by_id;

    public function handle()
    {
        $invitation = WorkspaceInvitation::query()
            ->where('workspace_id', $this->workspace_id)
            ->where('id', $this->invitation_id)
            ->whereNull('accepted_at')
            ->firstOrFail();

        $invitation->delete();

        return $invitation;
    }

    public function fired()
    {
        logger('Workspace invitation cancelled', [
            'workspace_id' => $this->workspace_id,
            'invitation_id' => $this->invitation_id,
            'cancelled_by_id' => $this->cancelled_by_id,
        ]);
    }
}
