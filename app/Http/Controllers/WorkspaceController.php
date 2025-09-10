<?php

namespace App\Http\Controllers;

use App\Events\WorkspaceCreated;
use App\Models\Workspace;
use App\Services\WorkspaceRoleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $workspaces = $user?->workspaces?->map(fn ($w) => [
            'uuid' => $w->uuid,
            'slug' => $w->slug,
            'name' => $w->name,
        ]) ?? collect();

        return Inertia::render('workspace/Index', [
            'workspaces' => $workspaces,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $workspace = Workspace::create([
            'name' => $validated['name'],
            'owner_id' => $request->user()->id,
        ]);

        // Attach owner as admin
        $workspace->users()->attach($request->user()->id, ['role' => 'owner']);

        // Seed core roles for this workspace
        app(WorkspaceRoleService::class)->seedCoreRoles($workspace);

        // Assign Owner role scoped to this workspace
        /** @var PermissionRegistrar $registrar */
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($workspace->getKey());
        $request->user()->assignRole('Owner');

        // Emit event for auditing/real-time pipelines
        WorkspaceCreated::fire(
            workspace_id: $workspace->id,
            workspace_name: $workspace->name,
            owner_id: $workspace->owner_id
        );

        return redirect()->route('workspaces.index')->with('success', 'Workspace created');
    }

    public function open(string $slug, string $uuid)
    {
        return redirect()->route('tenant.dashboard', ['tenant_slug' => $slug, 'tenant_uuid' => $uuid]);
    }
}
