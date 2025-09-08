<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('creates a workspace and opens tenant dashboard', function () {
    // Seed roles and permissions first
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $user = User::factory()->create();

    Auth::login($user);
    $response = $this->post('/workspaces', ['name' => 'Acme Inc']);
    
    $workspace = Workspace::firstOrFail();
    
    // The workspace creation should redirect to the tenant dashboard
    $response->assertRedirect(route('tenant.dashboard', [
        'tenant_slug' => $workspace->slug,
        'tenant_uuid' => $workspace->uuid
    ]));

    Auth::login($user);
    $response = $this->get(route('tenant.dashboard', [
        'tenant_slug' => $workspace->slug, 
        'tenant_uuid' => $workspace->uuid
    ]));
    
    $response->assertSuccessful();
});
