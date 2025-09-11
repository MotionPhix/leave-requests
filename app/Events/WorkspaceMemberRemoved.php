<?php

namespace App\Events;

use App\Models\User;
use App\Models\Workspace;
use Thunk\Verbs\Event;

class WorkspaceMemberRemoved extends Event
{
    public string $workspace_id;

    public string $user_uuid;

    public string $removed_by_id;

    public function handle()
    {
        $user = User::where('uuid', $this->user_uuid)->firstOrFail();
        $workspace = Workspace::find($this->workspace_id);

        // Remove user from workspace and clear roles
        $workspace->users()->detach($user->id);

        // Set permission context and clear roles
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($this->workspace_id);
        $user->syncRoles([]);

        return $user;
    }

    public function fired()
    {
        logger('Workspace member removed', [
            'workspace_id' => $this->workspace_id,
            'user_uuid' => $this->user_uuid,
            'removed_by_id' => $this->removed_by_id,
        ]);
    }
}
