<?php

use Illuminate\Support\Facades\Route;

Route::prefix('app/{tenant_slug}/{tenant_uuid}')
  ->middleware(['auth', 'workspace'])
  ->group(function () {
    // Tenant dashboard placeholder
    Route::get('/dashboard', [\App\Http\Controllers\Tenant\DashboardController::class, 'index'])
      ->name('tenant.dashboard');

    Route::prefix('members')
      ->middleware(['role:Owner|Admin'])
      ->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\MembersController::class, 'index'])->name('tenant.members.index');
        Route::post('/', [\App\Http\Controllers\Tenant\MembersController::class, 'store'])->name('tenant.members.store');
        Route::put('/{userUuid}', [\App\Http\Controllers\Tenant\MembersController::class, 'update'])->name('tenant.members.update');
        Route::delete('/{userUuid}', [\App\Http\Controllers\Tenant\MembersController::class, 'destroy'])->name('tenant.members.destroy');

        // Invitations
        Route::get('/invitations', [\App\Http\Controllers\Tenant\InvitationsController::class, 'index'])->name('tenant.invitations.index');
        Route::post('/invitations', [\App\Http\Controllers\Tenant\InvitationsController::class, 'store'])->name('tenant.invitations.store');
      });

    // Leave management within tenant context
    Route::prefix('leave')
      ->group(function () {
        // Employee leave requests
        Route::get('/requests', [\App\Http\Controllers\Employee\LeaveRequestController::class, 'index'])->name('tenant.leave-requests.index');
        Route::get('/requests/create', [\App\Http\Controllers\Employee\LeaveRequestController::class, 'create'])->name('tenant.leave-requests.create');
        Route::post('/requests', [\App\Http\Controllers\Employee\LeaveRequestController::class, 'store'])->name('tenant.leave-requests.store');
        Route::get('/requests/{leaveRequest}', [\App\Http\Controllers\Employee\LeaveRequestController::class, 'show'])->name('tenant.leave-requests.show');
        Route::patch('/requests/{leaveRequest}/cancel', [\App\Http\Controllers\Employee\LeaveRequestController::class, 'cancel'])->name('tenant.leave-requests.cancel');

        // Admin leave management
        Route::middleware(['role:Owner|Admin|HR|Manager'])
          ->prefix('admin')
          ->group(function () {
            Route::get('/requests', [\App\Http\Controllers\Admin\LeaveRequestController::class, 'index'])->name('tenant.admin.leave-requests.index');
            Route::get('/requests/{leaveRequest}', [\App\Http\Controllers\Admin\LeaveRequestController::class, 'show'])->name('tenant.admin.leave-requests.show');
            Route::patch('/requests/{leaveRequest}', [\App\Http\Controllers\Admin\LeaveRequestController::class, 'update'])->name('tenant.admin.leave-requests.update');
          });
      });
  });

// Public route to accept invitation (signed)
Route::get('/invitations/accept/{workspace}/{token}', \App\Http\Controllers\InvitationAcceptController::class)
  ->middleware(['signed'])
  ->name('invitations.accept');
