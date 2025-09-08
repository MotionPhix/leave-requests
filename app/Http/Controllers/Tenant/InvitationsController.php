<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InviteMemberRequest;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Services\WorkspaceRoleService;

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

        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'token' => Str::random(64),
            'inviter_id' => Auth::id(),
            'expires_at' => now()->addDays(7),
        ]);

    // Send email to the invited address
        Notification::route('mail', $invitation->email)
            ->notify(new WorkspaceInvitationNotification($invitation));

    return back()->with('success', 'Invitation sent.');
    }
}
