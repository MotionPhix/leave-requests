<?php

use Illuminate\Support\Facades\Route;

// =====================================
// TENANT-SPECIFIC ROUTES
// =====================================
// All routes here are for workspace-specific functionality
// Pattern: /{tenant:slug}/tenant:{uuid}/feature

Route::prefix('{tenant_slug}/{tenant_uuid}')
    ->where([
        'tenant_slug' => '[a-z0-9-]+',
        'tenant_uuid' => '[a-f0-9-]{36}',
    ])
    ->middleware(['auth', 'workspace'])
    ->group(function () {
        // Tenant dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Tenant\DashboardController::class, 'index'])
            ->name('tenant.dashboard');

        Route::prefix('members')
            ->middleware(['role:Owner|Super Admin|HR|Manager|Admin'])
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\MembersController::class, 'index'])->name('tenant.members.index');
                Route::post('/', [\App\Http\Controllers\Tenant\MembersController::class, 'store'])->name('tenant.members.store');
                Route::put('/{userUuid}', [\App\Http\Controllers\Tenant\MembersController::class, 'update'])->name('tenant.members.update');
                Route::delete('/{userUuid}', [\App\Http\Controllers\Tenant\MembersController::class, 'destroy'])->name('tenant.members.destroy');
            });

        // Invitations (authorize in controller: owner or privileged roles)
        Route::get('members/invitations', [\App\Http\Controllers\Tenant\InvitationsController::class, 'index'])->name('tenant.invitations.index');
        Route::post('members/invitations', [\App\Http\Controllers\Tenant\InvitationsController::class, 'store'])->name('tenant.invitations.store');
        Route::get('dashboard/invite-member', [\App\Http\Controllers\Tenant\InvitationsController::class, 'create'])->name('tenant.dashboard.invite-member');

        // Leave Types management within tenant context
        Route::prefix('leave-types')
            ->middleware(['role:Owner|Manager'])
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'index'])->name('tenant.leave-types.index');
                Route::get('/create', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'create'])->name('tenant.leave-types.create');
                Route::post('/', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'store'])->name('tenant.leave-types.store');
                Route::get('/{leaveType:uuid}/edit', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'edit'])->name('tenant.leave-types.edit');
                Route::put('/{leaveType}', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'update'])->name('tenant.leave-types.update');
                Route::delete('/{leaveType}', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'destroy'])->name('tenant.leave-types.destroy');
            });

        // Departments management within tenant context
        Route::prefix('departments')
            ->middleware(['role:Owner|Manager'])
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\DepartmentController::class, 'index'])->name('tenant.departments.index');
                Route::get('/create', [\App\Http\Controllers\Tenant\DepartmentController::class, 'create'])->name('tenant.departments.create');
                Route::post('/', [\App\Http\Controllers\Tenant\DepartmentController::class, 'store'])->name('tenant.departments.store');
                Route::get('/{department:uuid}/edit', [\App\Http\Controllers\Tenant\DepartmentController::class, 'edit'])->name('tenant.departments.edit');
                Route::put('/{department}', [\App\Http\Controllers\Tenant\DepartmentController::class, 'update'])->name('tenant.departments.update');
                Route::delete('/{department}', [\App\Http\Controllers\Tenant\DepartmentController::class, 'destroy'])->name('tenant.departments.destroy');
            });

        // Reports within tenant context
        Route::prefix('reports')
            ->middleware(['role:Owner|Manager'])
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\ReportController::class, 'index'])->name('tenant.reports.index');
                Route::get('/leave-summary', [\App\Http\Controllers\Tenant\ReportController::class, 'leaveSummary'])->name('tenant.reports.leave-summary');
                Route::get('/employee-usage', [\App\Http\Controllers\Tenant\ReportController::class, 'employeeUsage'])->name('tenant.reports.employee-usage');
                Route::get('/department-analysis', [\App\Http\Controllers\Tenant\ReportController::class, 'departmentAnalysis'])->name('tenant.reports.department-analysis');
            });

        // Holidays management within tenant context
        Route::prefix('holidays')
            ->middleware(['role:Owner|Super Admin|HR|Manager|Admin'])
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\HolidayController::class, 'index'])->name('tenant.holidays.index');
                Route::get('/create', [\App\Http\Controllers\Tenant\HolidayController::class, 'create'])->name('tenant.holidays.create');
                Route::post('/', [\App\Http\Controllers\Tenant\HolidayController::class, 'store'])->name('tenant.holidays.store');
                Route::get('/{holiday:uuid}/edit', [\App\Http\Controllers\Tenant\HolidayController::class, 'edit'])->name('tenant.holidays.edit');
                Route::put('/{holiday}', [\App\Http\Controllers\Tenant\HolidayController::class, 'update'])->name('tenant.holidays.update');
                Route::delete('/{holiday}', [\App\Http\Controllers\Tenant\HolidayController::class, 'destroy'])->name('tenant.holidays.destroy');
            });

        // Leave management within tenant context
    Route::prefix('leave')
            ->group(function () {
                // Employee leave requests - Only for non-owners who can actually request leave
                Route::middleware(['role_or_permission:Employee|HR Manager|Department Manager|Team Lead|Project Manager|Senior Employee|leave-requests.create'])
                    ->group(function () {
                        Route::get('/requests/create', [\App\Http\Controllers\Employee\LeaveRequestController::class, 'create'])->name('tenant.leave-requests.create');
                        Route::post('/requests', [\App\Http\Controllers\Employee\LeaveRequestController::class, 'store'])->name('tenant.leave-requests.store');
                    });

                // General leave request viewing (everyone can view their own)
                Route::get('/requests', [\App\Http\Controllers\Employee\LeaveRequestController::class, 'index'])->name('tenant.leave-requests.index');
                Route::get('/requests/{leaveRequest}', [\App\Http\Controllers\Employee\LeaveRequestController::class, 'show'])->name('tenant.leave-requests.show');
                Route::patch('/requests/{leaveRequest}/cancel', [\App\Http\Controllers\Employee\LeaveRequestController::class, 'cancel'])->name('tenant.leave-requests.cancel');

                // Admin leave management
                Route::middleware(['role:Owner|Super Admin|HR|Manager|Team Lead'])
                    ->prefix('admin')
                    ->group(function () {
                        Route::get('/requests', [\App\Http\Controllers\Admin\LeaveRequestController::class, 'index'])->name('tenant.admin.leave-requests.index');
                        Route::get('/requests/{leaveRequest}', [\App\Http\Controllers\Admin\LeaveRequestController::class, 'show'])->name('tenant.admin.leave-requests.show');
                        Route::patch('/requests/{leaveRequest}', [\App\Http\Controllers\Admin\LeaveRequestController::class, 'update'])->name('tenant.admin.leave-requests.update');
                    });
            });
    });
