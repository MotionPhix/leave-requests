<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {

  // Admin routes
  Route::prefix('admin')->group(function () {

    Route::get(
      '/calendar',
      [\App\Http\Controllers\Admin\Api\CalendarController::class, 'index']
    )->name('api.admin.calendar');

    Route::put(
      '/calendar/u/{leaveRequest}',
      [\App\Http\Controllers\Admin\Api\CalendarController::class, 'update']
    )->name('api.admin.calendar.update');

    Route::get(
      '/stats',
      [\App\Http\Controllers\Admin\Api\DashboardController::class, 'stats']
    )->name('api.admin.stats');

    Route::get(
      '/users',
      function () {
        return \App\Models\User::role('Employee')
          ->select(['id', 'uuid', 'name'])
          ->orderBy('name')
          ->get();
      }
    )->name('api.admin.users');

    Route::get(
      '/leave-types',
      function () {
        return \App\Models\LeaveType::all(['id', 'name']);
      }
    )->name('api.admin.leave-types');
  });

  // Employee routes
  Route::get(
    '/calendar',
    [\App\Http\Controllers\Employee\Api\CalendarController::class, 'index']
  )->name('api.calendar');

  // Notification routes
  Route::get(
    '/notifications',
    [
      NotificationController::class,
      'getNotifications'
    ]
  );

  Route::post(
    '/notifications/{id}/mark-as-read',
    [
      NotificationController::class,
      'markAsRead'
    ]
  );

  Route::post(
    '/notifications/mark-all-as-read',
    [
      NotificationController::class,
      'markAllAsRead'
    ]
  );
});
