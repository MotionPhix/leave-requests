<?php

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('shows leave type details page for HR without error', function () {
    // Create HR user and assign role if using spatie/permission
    $user = User::factory()->createOne();

    // Attempt to assign HR role if roles table exists
    try {
        if (Schema::hasTable('roles')) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'HR']);
            $user->assignRole($role);
        }
    } catch (\Throwable $e) {
        // If permissions not set up in test env, proceed without role
    }

    $leaveType = LeaveType::factory()->create();

    // Authenticate and bypass middleware to focus on controller behavior
    $this->actingAs($user);
    $this->withoutMiddleware();

    $this->get(route('admin.leave-types.show', ['leaveType' => $leaveType->uuid]))
        ->assertSuccessful();
});
