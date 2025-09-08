<?php

use App\Models\User;
use App\Models\Workspace;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticated users can access tenant dashboard with proper setup', function () {
    // Seed roles and permissions
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    
    // Add user to workspace
    $workspace->users()->attach($user->id, ['role' => 'owner']);
    
    // Set up permissions and create workspace-scoped roles
    setPermissionsTeamId($workspace->id);
    
    // Use WorkspaceRoleService to create workspace-scoped roles
    $roleService = new \App\Services\WorkspaceRoleService();
    $roleService->seedCoreRoles($workspace);
    
    $user->assignRole('Owner');
    
    $this->actingAs($user);

    $response = $this->get(route('tenant.dashboard', [
        'tenant_slug' => $workspace->slug,
        'tenant_uuid' => $workspace->uuid
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('tenant/Dashboard')
        ->has('workspace')
        ->has('stats')
    );
});
