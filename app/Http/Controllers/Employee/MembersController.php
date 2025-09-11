<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class MembersController extends Controller
{
    /**
     * Display team members for employees (read-only view)
     */
    public function index(Request $request): Response
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

        // Get all members including the owner for employee view
        $members = $workspace->users()
            ->with('roles')
            ->get()
            ->map(fn ($u) => [
                'uuid' => $u->uuid,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->roles->first()?->name,
            ])->toArray();

        return Inertia::render('employee/members/Index', [
            'members' => $members,
            'currentUser' => [
                'uuid' => $request->user()->uuid,
                'role' => $request->user()->roles->first()?->name,
            ],
        ]);
    }
}
