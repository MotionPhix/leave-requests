<?php

use App\Models\User;
use App\Models\Workspace;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the workspace selection page', function () {
    $response = $this->get('/workspaces');
    $response->assertRedirect('/login');
});

test('authenticated users can visit workspace selection', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/workspaces');
    $response->assertStatus(200);
});