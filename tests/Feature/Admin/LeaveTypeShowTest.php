<?php

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('shows leave type details page for HR without error', function () {
    // Seed permissions first
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    // Create HR user and workspace
    $user = User::factory()->createOne();
    $workspace = \App\Models\Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->users()->attach($user->id);

    // Set up workspace-scoped roles
    app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
    $roleService = new \App\Services\WorkspaceRoleService();
    $roleService->seedCoreRoles($workspace);
    
    $user->assignRole('HR');

    $leaveType = \App\Models\LeaveType::factory()->create(['workspace_id' => $workspace->id]);

    // Authenticate and bypass middleware to focus on controller behavior
    Auth::login($user);
    $this->withoutMiddleware();

    $this->get(route('admin.leave-types.show', ['leaveType' => $leaveType->uuid]))
        ->assertSuccessful();
});
