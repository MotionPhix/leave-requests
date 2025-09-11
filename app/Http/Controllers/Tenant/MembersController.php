<?php

namespace App\Http\Controllers\Tenant;

use App\Events\WorkspaceMemberInvited;
use App\Events\WorkspaceMemberRemoved;
use App\Events\WorkspaceMemberRoleUpdated;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\RolePermissionService;
use App\Services\WorkspaceRoleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Thunk\Verbs\Facades\Verbs;

class MembersController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService
    ) {}

    public function index(Request $request)
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('workspace');

        // Set permission team ID for role checking
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->getKey());

        // Get the workspace owner
        $ownerRole = Role::where('workspace_id', $workspace->getKey())
            ->where('name', 'Owner')
            ->first();

        $ownerId = null;
        if ($ownerRole) {
            $ownerId = \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->where('role_id', $ownerRole->id)
                ->where('workspace_id', $workspace->getKey())
                ->value('model_id');
        }

        // Get members excluding the owner
        $members = $workspace->users()
            ->with('roles')
            ->when($ownerId, function ($query) use ($ownerId) {
                return $query->where('users.id', '!=', $ownerId);
            })
            ->get()
            ->map(fn ($u) => [
                'uuid' => $u->uuid,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->roles->first()?->name,
            ]);

        // Get assignable roles for the current user
        $assignableRoles = $this->rolePermissionService->getAssignableRoles($request->user(), $workspace)
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $this->rolePermissionService->getWorkspaceRoleDefinitions()[$role->name]['label'] ?? $role->name,
                ];
            });

        $roles = Role::query()
            ->where('workspace_id', $workspace->getKey())
            ->pluck('name');

        $invitations = WorkspaceInvitation::query()
            ->where('workspace_id', $workspace->getKey())
            ->whereNull('accepted_at')
            ->latest()
            ->get(['id', 'email', 'role', 'expires_at']);

        return Inertia::render('tenant/members/Index', [
            'members' => $members,
            'roles' => $roles,
            'assignableRoles' => $assignableRoles,
            'invitations' => $invitations,
            'currentUser' => [
                'uuid' => $request->user()->uuid,
                'role' => $request->user()->roles->first()?->name,
            ],
            'canManageRoles' => $this->rolePermissionService->canManageRoles($request->user(), $workspace),
        ]);
    }

    public function store(Request $request)
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('workspace');

        // Get valid role names for this workspace
        $validRoles = $this->rolePermissionService->getAssignableRoles($request->user(), $workspace)
            ->pluck('name')
            ->toArray();

        $validated = $request->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')],
            'role' => ['required', Rule::in($validRoles)],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        // Ensure roles exist for this workspace
        app(WorkspaceRoleService::class)->seedCoreRoles($workspace);

        // Attach member and assign role (team scoped)
        $workspace->users()->syncWithoutDetaching([$user->id]);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->getKey());
        $user->syncRoles([$validated['role']]);

        Verbs::fire(WorkspaceMemberInvited::fire($workspace, $user, $validated['role']));

        return back()->with('success', 'Member added to workspace');
    }

    public function update(Request $request, string $userUuid)
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('workspace');
        // Get valid role names for this workspace
        $validRoles = $this->rolePermissionService->getAssignableRoles($request->user(), $workspace)
            ->pluck('name')
            ->toArray();

        $validated = $request->validate([
            'role' => ['required', Rule::in($validRoles)],
        ]);

        // Fire the Verbs event instead of direct database operations
        WorkspaceMemberRoleUpdated::commit(
            workspace_id: (string) $workspace->id,
            user_uuid: $userUuid,
            new_role: $validated['role'],
            updated_by_id: (string) $request->user()->id
        );

        return back()->with('success', 'Member role updated');
    }

    public function destroy(Request $request, string $userUuid)
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('workspace');

        // Fire the Verbs event instead of direct database operations
        WorkspaceMemberRemoved::commit(
            workspace_id: (string) $workspace->id,
            user_uuid: $userUuid,
            removed_by_id: (string) $request->user()->id
        );

        return back()->with('success', 'Member removed');
    }
}
