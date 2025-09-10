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

  // Calendar statistics
  Route::get(
    '/calendar/statistics',
    [\App\Http\Controllers\Employee\Api\CalendarController::class, 'statistics']
  )->name('api.calendar.statistics');

  // Export functionality
  Route::get(
    '/calendar/export/{format}',
    [\App\Http\Controllers\Employee\Api\CalendarController::class, 'export']
  )->where('format', 'ics|csv|pdf')
  ->name('api.calendar.export');

  // Leave types for filtering
  Route::get(
    '/calendar/leave-types',
    [\App\Http\Controllers\Employee\Api\CalendarController::class, 'leaveTypes']
  )->name('api.calendar.leave-types');

  // Holidays for calendar display
  Route::get(
    '/calendar/holidays',
    [\App\Http\Controllers\Employee\Api\CalendarController::class, 'holidays']
  )->name('api.calendar.holidays');

  // Comprehensive Holiday API
  Route::prefix('holidays')->name('api.holidays.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\HolidayController::class, 'index'])->name('index');
    Route::get('/stats', [\App\Http\Controllers\Api\HolidayController::class, 'stats'])->name('stats');
    Route::get('/conflicts', [\App\Http\Controllers\Api\HolidayController::class, 'conflicts'])->name('conflicts');
    Route::get('/export/{format}', [\App\Http\Controllers\Api\HolidayController::class, 'export'])->name('export');
  });

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

  Route::delete(
    '/notifications/clear-all',
    [
      NotificationController::class,
      'clearAllNotifications'
    ]
  );

  Route::delete(
    '/notifications/{id}',
    [
      NotificationController::class,
      'deleteNotification'
    ]
  );
});
