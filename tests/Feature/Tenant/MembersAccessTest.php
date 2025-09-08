<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createWorkspaceAs(User $user): Workspace
{
    Auth::login($user);
    test()->post('/workspaces', ['name' => 'Acme ' . uniqid()])->assertRedirect('/workspaces');
    return Workspace::latest()->firstOrFail();
}

it('allows the owner to access members index', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceAs($owner);

    // Set up permissions and create workspace-scoped roles
    app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
    
    // Use WorkspaceRoleService to create workspace-scoped roles
    $roleService = new \App\Services\WorkspaceRoleService();
    $roleService->seedCoreRoles($workspace);
    
    $owner->assignRole('Owner');

    Auth::login($owner);
    $this->get(route('tenant.members.index', [
        'tenant_slug' => $workspace->slug,
        'tenant_uuid' => $workspace->uuid,
    ]))->assertSuccessful();
});

it('forbids non-members from accessing members index', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $workspace = createWorkspaceAs($owner);

    Auth::login($other);
    $this->get(route('tenant.members.index', [
        'tenant_slug' => $workspace->slug,
        'tenant_uuid' => $workspace->uuid,
    ]))->assertForbidden();
});
