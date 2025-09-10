<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('seeds super admin and owner and workspace roles', function () {
    // Run the seeder under test
    $this->seed(\Database\Seeders\UserSeeder::class);

    // Super Admin role and user
    expect(Role::where('name', 'Super Admin')->exists())->toBeTrue();
    $super = User::where('email', 'superadmin@qhub.com')->first();
    expect($super)->not->toBeNull();
    expect($super->hasRole('Super Admin'))->toBeTrue();

    // Workspace owner and workspace
    $owner = User::where('email', 'owner@qhub.com')->first();
    expect($owner)->not->toBeNull();

    $workspace = Workspace::where('owner_id', $owner->id)->first();
    expect($workspace)->not->toBeNull();

    // Owner should be attached to the workspace via pivot
    expect($workspace->users()->where('user_id', $owner->id)->exists())->toBeTrue();
    expect($owner->hasRole('Owner'))->toBeTrue();

    // Core workspace roles seeded (Owner, HR, Manager, Employee)
    foreach (['Owner', 'HR', 'Manager', 'Employee'] as $roleName) {
        expect(Role::where('name', $roleName)->exists())->toBeTrue();
    }
});
