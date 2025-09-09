<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;

class InvitationRegisterController extends Controller
{
    public function __invoke(Request $request, Workspace $workspace, string $token)
    {
        $invitation = WorkspaceInvitation::query()
            ->where('workspace_id', $workspace->id)
            ->where('token', $token)
            ->firstOrFail();

        // Check if invitation is still valid
        if ($invitation->accepted_at !== null) {
            return redirect()->route('tenant.dashboard', [
                'tenant_slug' => $workspace->slug,
                'tenant_uuid' => $workspace->uuid
            ])->with('info', 'Invitation already accepted.');
        }

        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            return redirect()->route('login')->withErrors(['invitation' => 'This invitation has expired.']);
        }

        // Check if user already exists
        $existingUser = User::where('email', $invitation->email)->first();
        if ($existingUser) {
            return redirect()->route('invitations.accept', [
                'workspace' => $workspace->id,
                'token' => $token
            ])->withErrors(['email' => 'An account with this email already exists. Please log in instead.']);
        }

        if ($request->isMethod('get')) {
            return Inertia::render('auth/InvitationRegister', [
                'invitation' => [
                    'email' => $invitation->email,
                    'workspace_name' => $workspace->name,
                    'role' => $invitation->role,
                ],
                'workspace' => $workspace,
                'token' => $token,
            ]);
        }

        // Handle POST request (registration)
        $request->validate([
            'name' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        // Attach user to workspace and assign role
        $workspace->users()->syncWithoutDetaching([$user->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        $user->assignRole($invitation->role);

        // Mark invitation as accepted
        $invitation->forceFill([
            'accepted_at' => now(),
        ])->save();

        // Log the user in
        Auth::login($user);

        return redirect()->route('tenant.dashboard', [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid
        ])->with('success', 'Welcome! You have successfully joined the workspace.');
    }
}
