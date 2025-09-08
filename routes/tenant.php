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
