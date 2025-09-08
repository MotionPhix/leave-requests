<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'gender' => 'male',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Check if the registration was successful first
    $response->assertStatus(302); // Should be a redirect

    $this->assertAuthenticated();
    // New users are redirected to workspace selection in the central system
    $response->assertRedirect(route('workspaces.index', absolute: false));
});
