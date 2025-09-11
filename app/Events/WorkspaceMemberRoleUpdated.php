<?php

namespace App\Events;

use App\Models\User;
use App\Services\WorkspaceRoleService;
use Thunk\Verbs\Event;

class WorkspaceMemberRoleUpdated extends Event
{
    public string $workspace_id;

    public string $user_uuid;

    public string $new_role;

    public string $updated_by_id;

    public function handle()
    {
        $user = User::where('uuid', $this->user_uuid)->firstOrFail();

        // Ensure core roles exist for safety
        app(WorkspaceRoleService::class)->seedCoreRoles(
            \App\Models\Workspace::find($this->workspace_id)
        );

        // Set the permission team context and update role
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($this->workspace_id);
        $user->syncRoles([$this->new_role]);

        return $user;
    }

    public function fired()
    {
        logger('Workspace member role updated', [
            'workspace_id' => $this->workspace_id,
            'user_uuid' => $this->user_uuid,
            'new_role' => $this->new_role,
            'updated_by_id' => $this->updated_by_id,
        ]);
    }
}
