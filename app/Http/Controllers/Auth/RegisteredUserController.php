<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(Request $request): Response
    {
        $invitation = null;
        $invitationToken = $request->get('invitation');

        // If there's an invitation token, find the invitation
        if ($invitationToken) {
            $invitation = WorkspaceInvitation::where('token', $invitationToken)
                ->with('workspace')
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->first();
        }

        return Inertia::render('auth/Register', [
            'invitation' => $invitation ? [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'workspace' => [
                    'name' => $invitation->workspace->name,
                    'slug' => $invitation->workspace->slug,
                ],
            ] : null,
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'gender' => 'required|in:male,female',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'invitation_token' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'gender' => $request->gender,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));
        Auth::login($user);

        // Note: Owner role is workspace-scoped and will be assigned 
        // when the user creates their first workspace

        // Handle invitation-based registration
        if ($request->invitation_token) {
            return $this->handleInvitationRegistration($request, $user);
        }

        // For regular registration, redirect to workspace list in central system
        return redirect()->route('workspaces.index')->with('welcome', 'Welcome! Please select or create a workspace to continue.');
    }

    /**
     * Handle registration with workspace invitation
     */
    private function handleInvitationRegistration(Request $request, User $user): RedirectResponse
    {
        $invitation = WorkspaceInvitation::where('token', $request->invitation_token)
            ->with('workspace')
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invitation) {
            return redirect()->route('workspaces.select')
                ->withErrors(['invitation' => 'Invalid or expired invitation.']);
        }

        // Verify email matches invitation
        if (strcasecmp($user->email, $invitation->email) !== 0) {
            return redirect()->route('workspaces.select')
                ->withErrors(['invitation' => 'Registration email must match invitation email.']);
        }

        $workspace = $invitation->workspace;

    // Add user to workspace (pivot role kept for legacy visibility; actual permissions via Spatie roles)
    $workspace->users()->attach($user->id, ['role' => 'member']);

    // Ensure core roles exist for this workspace before assigning
    app(\App\Services\WorkspaceRoleService::class)->seedCoreRoles($workspace);

    // Set workspace context and assign the invited role (Owner/Admin/HR/Manager/Employee)
    setPermissionsTeamId($workspace->id);
    $user->assignRole($invitation->role);

        // Mark invitation as accepted
        $invitation->update(['accepted_at' => now()]);

        return redirect()->route('tenant.dashboard', [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
        ])->with('success', 'Welcome! You have successfully joined the workspace.');
    }
}
