<?php

use Illuminate\Support\Facades\Route;

// =====================================
// EMPLOYEE ROUTES
// =====================================
// Routes for employee-specific functionality
// Accessible by: All workspace members (Employee role and above)

Route::prefix('{tenant_slug}/{tenant_uuid}')
    ->where([
        'tenant_slug' => '[a-z0-9-]+',
        'tenant_uuid' => '[a-f0-9-]{36}',
    ])
    ->middleware(['auth', 'workspace'])
    ->group(function () {
        // Employee Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Tenant\DashboardController::class, 'index'])
            ->name('tenant.dashboard');

        // Employee Leave Requests (their own requests)
        Route::prefix('leave-requests')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\LeaveRequestController::class, 'index'])
                    ->name('tenant.leave-requests.index');
                Route::get('/create', [\App\Http\Controllers\Tenant\LeaveRequestController::class, 'create'])
                    ->name('tenant.leave-requests.create');
                Route::post('/', [\App\Http\Controllers\Tenant\LeaveRequestController::class, 'store'])
                    ->name('tenant.leave-requests.store');
                Route::get('/{leaveRequest}', [\App\Http\Controllers\Tenant\LeaveRequestController::class, 'show'])
                    ->name('tenant.leave-requests.show');
                Route::patch('/{leaveRequest}/cancel', [\App\Http\Controllers\Tenant\LeaveRequestController::class, 'cancel'])
                    ->name('tenant.leave-requests.cancel');
            });

        // Team Members (view-only for regular employees)
        Route::get('members', [\App\Http\Controllers\Tenant\MembersController::class, 'index'])
            ->name('tenant.members.index');

        // Employee Calendar View (view-only)
        Route::prefix('calendar')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\CalendarController::class, 'index'])
                    ->name('tenant.calendar.index');
                Route::get('/events', [\App\Http\Controllers\Tenant\CalendarController::class, 'events'])
                    ->name('tenant.calendar.events');
                Route::get('/conflicts', [\App\Http\Controllers\Tenant\CalendarController::class, 'conflicts'])
                    ->name('tenant.calendar.conflicts');
            });

        // Employee Holidays (view-only)
        Route::get('holidays', [\App\Http\Controllers\Tenant\HolidayController::class, 'index'])
            ->name('tenant.holidays.index');
    });