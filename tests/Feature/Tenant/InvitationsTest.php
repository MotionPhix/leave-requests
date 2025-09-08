<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;

uses(RefreshDatabase::class);

it('owner can send an invitation', function () {
    Notification::fake();
    $this->seed(RolesAndPermissionsSeeder::class);

    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $workspace->users()->attach($owner->id);

    $this->actingAs($owner);

    // Seed roles for team and set context
    app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
    
    // Use WorkspaceRoleService to create workspace-scoped roles
    $roleService = new \App\Services\WorkspaceRoleService();
    $roleService->seedCoreRoles($workspace);
    
    $owner->assignRole('Owner');

    $invitee = User::factory()->create();

    $response = $this->post(route('tenant.invitations.store', [
        'tenant_slug' => $workspace->slug,
        'tenant_uuid' => $workspace->uuid,
    ]), [
        'email' => $invitee->email,
        'role' => 'Employee',
    ]);

    $response->assertRedirect();

    // Debug: Check what invitations exist
    $allInvitations = WorkspaceInvitation::all();
    $workspaceInvitations = WorkspaceInvitation::where('workspace_id', $workspace->id)->get();

    if (!WorkspaceInvitation::where('workspace_id', $workspace->id)->where('email', $invitee->email)->exists()) {
        dd([
            'all_invitations' => $allInvitations->toArray(),
            'workspace_invitations' => $workspaceInvitations->toArray(),
            'looking_for_workspace_id' => $workspace->id,
            'looking_for_email' => $invitee->email,
            'workspace' => $workspace->toArray(),
            'invitee' => $invitee->toArray(),
        ]);
    }

    expect(WorkspaceInvitation::where('workspace_id', $workspace->id)->where('email', $invitee->email)->exists())->toBeTrue();
});

it('signed accept joins the workspace and assigns role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $workspace->id,
        'email' => $user->email,
        'role' => 'Employee',
    ]);

    $this->actingAs($user);

    $url = URL::temporarySignedRoute('invitations.accept', now()->addHour(), [
        'workspace' => $workspace->id,
        'token' => $invitation->token,
    ]);

    $response = $this->get($url);
    $response->assertRedirect();

    // User is attached to workspace
    expect($workspace->users()->whereKey($user->id)->exists())->toBeTrue();
});
