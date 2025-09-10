<?php

namespace App\Http\Controllers\Tenant;

use App\Events\WorkspaceMemberInvited;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\WorkspaceRoleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Thunk\Verbs\Facades\Verbs;

class MembersController extends Controller
{
    public function index(Request $request)
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('workspace');

        $members = $workspace->users()->with('roles')->get()->map(fn ($u) => [
            'uuid' => $u->uuid,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->roles->first()?->name,
        ]);

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
            'invitations' => $invitations,
        ]);
    }

    public function store(Request $request)
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('workspace');

        $validated = $request->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')],
            'role' => ['required', Rule::in(['Owner', 'Admin', 'HR', 'Manager', 'Employee'])],
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
        $validated = $request->validate([
            'role' => ['required', Rule::in(['Owner', 'Admin', 'HR', 'Manager', 'Employee'])],
        ]);

        $user = User::where('uuid', $userUuid)->firstOrFail();

        // Ensure core roles exist for safety
        app(\App\Services\WorkspaceRoleService::class)->seedCoreRoles($workspace);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->getKey());
        $user->syncRoles([$validated['role']]);

        return back()->with('success', 'Member role updated');
    }

    public function destroy(Request $request, string $userUuid)
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('workspace');
        $user = User::where('uuid', $userUuid)->firstOrFail();
        $workspace->users()->detach($user->id);
        $user->syncRoles([]);

        return back()->with('success', 'Member removed');
    }
}
