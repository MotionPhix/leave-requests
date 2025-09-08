<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceSelectionController extends Controller
{
    /**
     * Show workspace selection page
     */
    public function index(): Response
    {
        $user = Auth::user();
        $workspaces = $user->workspaces()->with('owner')->get();

        // Always show workspace selection interface
        return Inertia::render('workspace/Index', [
            'workspaces' => $workspaces->map(function ($workspace) use ($user) {
                return [
                    'id' => $workspace->id,
                    'uuid' => $workspace->uuid,
                    'slug' => $workspace->slug,
                    'name' => $workspace->name,
                    'owner' => $workspace->owner->name,
                    'is_owner' => $workspace->owner_id === $user->id,
                    'member_count' => $workspace->users()->count(),
                ];
            }),
        ]);
    }

    /**
     * Create a new workspace
     */
    public function create(): Response
    {
        return Inertia::render('workspace/Create');
    }

    /**
     * Store a new workspace and redirect to it
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();

        // Create workspace
        $workspace = Workspace::create([
            'name' => $request->name,
            'owner_id' => $user->id,
        ]);

    // Add user to workspace as owner in pivot
    $workspace->users()->attach($user->id, ['role' => 'owner']);

    // Ensure core workspace-scoped roles exist (Owner, Admin, HR, Manager, Employee)
    app(\App\Services\WorkspaceRoleService::class)->seedCoreRoles($workspace);

    // Set workspace (team) context and assign the workspace-scoped Owner role
    setPermissionsTeamId($workspace->id);
    $user->assignRole('Owner');

        // Redirect back to the workspace selection list (as tests expect)
        return redirect()->route('workspaces.index')
            ->with('success', 'Workspace created successfully!');
    }

    /**
     * Switch to a specific workspace
     */
    public function switch(Workspace $workspace): RedirectResponse
    {
        // Verify user has access to this workspace
        $user = Auth::user();
        $isOwner = $workspace->owner_id == $user->id;
        $isMember = $workspace->users->contains($user->id);
        
        if (!$isOwner && !$isMember) {
            abort(403, 'You do not have access to this workspace.');
        }

        return redirect()->route('tenant.dashboard', [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
        ]);
    }
}
