<?php

namespace App\Http\Controllers\Tenant;

use App\Events\WorkspaceInvitationCancelled;
use App\Events\WorkspaceMemberInvited;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InviteMemberRequest;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\WorkspaceRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvitationsController extends Controller
{
    public function index(Request $request, string $tenant_slug, string $tenant_uuid)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();
        /** @var \App\Models\Workspace $workspace */
        $invitations = WorkspaceInvitation::query()
            ->where('workspace_id', $workspace->id)
            ->latest()
            ->get();

        return Inertia::render('tenant/Members/Index', [
            'workspace' => $workspace,
            'invitations' => $invitations,
        ]);
    }

    public function store(InviteMemberRequest $request, string $tenant_slug, string $tenant_uuid): RedirectResponse
    {
        $data = $request->validated();
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Authorization: only Owner/Admin/HR/Manager can invite
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        if (! $request->user()->hasAnyRole(['Owner', 'Admin', 'HR', 'Manager', 'Super Admin'])) {
            abort(403);
        }

        // Ensure roles exist for this workspace
        app(WorkspaceRoleService::class)->seedCoreRoles($workspace);

        // Fire the Verbs event instead of direct database operations
        WorkspaceMemberInvited::commit(
            workspace_id: (string) $workspace->id,
            email: $data['email'],
            role: $data['role'],
            inviter_id: (string) Auth::id(),
            expires_at: now()->addDays(7)->toISOString()
        );

        return back()->with('success', 'Invitation sent.');
    }

    public function create(Request $request, string $tenant_slug, string $tenant_uuid)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Authorization: only Owner/Admin/HR/Manager can invite
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        if (! $request->user()->hasAnyRole(['Owner', 'Admin', 'HR', 'Manager', 'Super Admin'])) {
            abort(403);
        }

        return Inertia::render('tenant/dashboard/InviteMemberModal', [
            'workspace' => $workspace,
        ]);
    }

    public function destroy(Request $request, string $tenant_slug, string $tenant_uuid, int $invitationId): RedirectResponse
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Authorization: only Owner/Admin/HR/Manager can cancel invitations
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        if (! $request->user()->hasAnyRole(['Owner', 'Admin', 'HR', 'Manager', 'Super Admin'])) {
            abort(403);
        }

        // Fire the Verbs event instead of direct database operations
        WorkspaceInvitationCancelled::commit(
            workspace_id: (string) $workspace->id,
            invitation_id: $invitationId,
            cancelled_by_id: (string) $request->user()->id
        );

        return back()->with('success', 'Invitation cancelled successfully.');
    }
}
