<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('creates a workspace and opens tenant dashboard', function () {
    $user = User::factory()->create();

    Auth::login($user);
    $this->post('/workspaces', ['name' => 'Acme Inc'])
        ->assertRedirect('/workspaces');

    $workspace = Workspace::firstOrFail();

    Auth::login($user);
    $this->withoutMiddleware();
    $this->get(route('tenant.dashboard', ['tenant_slug' => $workspace->slug, 'tenant_uuid' => $workspace->uuid]))
        ->assertSuccessful();
});
