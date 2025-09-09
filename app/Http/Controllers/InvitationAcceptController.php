<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class InvitationAcceptController extends Controller
{
    public function __invoke(Request $request, Workspace $workspace, string $token): RedirectResponse
    {
        $invitation = WorkspaceInvitation::query()
            ->where('workspace_id', $workspace->id)
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->accepted_at !== null) {
            return redirect()->route('tenant.dashboard', ['tenant_slug' => $workspace->slug, 'tenant_uuid' => $workspace->uuid])
                ->with('info', 'Invitation already accepted.');
        }

        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            return redirect()->route('login')->withErrors(['invitation' => 'This invitation has expired.']);
        }

        /** @var User $user */
        $user = $request->user();

        // If user is not authenticated, redirect to invitation registration page
        if ($user === null) {
            return redirect()->route('invitations.register', [
                'workspace' => $workspace->id,
                'token' => $token
            ]);
        }

        // Ensure the logged in user matches the invitation email
        if (strcasecmp($user->email, $invitation->email) !== 0) {
            return redirect()->route('login')->withErrors(['invitation' => 'This invitation is for a different email address.']);
        }

        // Attach user to workspace and assign role within team scope
        $workspace->users()->syncWithoutDetaching([$user->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        $user->assignRole($invitation->role);

        $invitation->forceFill([
            'accepted_at' => now(),
        ])->save();

        return redirect()->route('tenant.dashboard', ['tenant_slug' => $workspace->slug, 'tenant_uuid' => $workspace->uuid])
            ->with('success', 'You have joined the workspace.');
    }
}
