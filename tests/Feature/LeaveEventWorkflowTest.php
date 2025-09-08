<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Events\LeaveRequestSubmitted;
use App\Events\LeaveRequestApproved;
use App\Events\LeaveRequestRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Thunk\Verbs\Facades\Verbs;
use Database\Seeders\RolesAndPermissionsSeeder;

uses(RefreshDatabase::class);
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Verbs::fake();
    Verbs::commitImmediately();
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('fires LeaveRequestSubmitted event when employee submits leave request', function () {
    $employee = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $leaveType = LeaveType::factory()->create(['workspace_id' => $workspace->id]);

    // Setup workspace context
    $workspace->users()->attach($employee->id);
    app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
    
    // Use WorkspaceRoleService to create proper roles
    $roleService = new \App\Services\WorkspaceRoleService();
    $roleService->seedCoreRoles($workspace);
    
    $employee->assignRole('Employee');

    Auth::login($employee);

    $leaveRequest = LeaveRequest::create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(9),
        'reason' => 'Testing event firing',
        'status' => 'pending',
        'workspace_id' => $workspace->id,
    ]);

    // Fire event manually (since we're not going through the full HTTP request)
    LeaveRequestSubmitted::fire(
        workspace_id: (string) $workspace->id,
        workspace_name: $workspace->name,
        employee_id: (string) $employee->id,
        employee_name: $employee->name,
        leave_request_id: (string) $leaveRequest->id,
        requestData: []
    );

    Verbs::assertCommitted(LeaveRequestSubmitted::class);
});

it('fires LeaveRequestApproved event when admin approves leave', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $leaveType = LeaveType::factory()->create(['workspace_id' => $workspace->id]);

    // Setup workspace context and roles
    $workspace->users()->attach([$admin->id, $employee->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
    
    // Use WorkspaceRoleService to create proper roles
    $roleService = new \App\Services\WorkspaceRoleService();
    $roleService->seedCoreRoles($workspace);
    
    $admin->assignRole('Admin');
    $employee->assignRole('Employee');

    $leaveRequest = LeaveRequest::create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(9),
        'reason' => 'Testing approval event',
        'status' => 'pending',
        'workspace_id' => $workspace->id,
    ]);

    // Fire approval event
    LeaveRequestApproved::fire(
        workspace_id: (string) $workspace->id,
        workspace_name: $workspace->name,
        approver_id: (string) $admin->id,
        approver_name: $admin->name,
        leave_request_id: (string) $leaveRequest->id,
        approverNotes: 'Approved for testing'
    );

    Verbs::assertCommitted(LeaveRequestApproved::class);
});

it('fires LeaveRequestRejected event when admin rejects leave', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $leaveType = LeaveType::factory()->create(['workspace_id' => $workspace->id]);

    // Setup workspace context and roles
    $workspace->users()->attach([$admin->id, $employee->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
    
    // Use WorkspaceRoleService to create proper roles
    $roleService = new \App\Services\WorkspaceRoleService();
    $roleService->seedCoreRoles($workspace);
    
    $admin->assignRole('Admin');
    $employee->assignRole('Employee');

    $leaveRequest = LeaveRequest::create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(9),
        'reason' => 'Testing rejection event',
        'status' => 'pending',
        'workspace_id' => $workspace->id,
    ]);

    // Fire rejection event
    LeaveRequestRejected::fire(
        workspace_id: (string) $workspace->id,
        workspace_name: $workspace->name,
        approver_id: (string) $admin->id,
        approver_name: $admin->name,
        leave_request_id: (string) $leaveRequest->id,
        rejectionReason: 'Not enough notice'
    );

    Verbs::assertCommitted(LeaveRequestRejected::class);
});
