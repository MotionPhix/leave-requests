<?php

use Illuminate\Support\Facades\Route;

Route::group([
  'prefix' => 'admin',
  'middleware' => ['auth', 'role:Admin|Manager|HR']
], function () {

  Route::controller(App\Http\Controllers\Admin\LeaveRequestController::class)
    ->group(function () {

      Route::get(
        '/leave-requests', 'index'
      )->name('admin.leave-requests.index');

      Route::get(
        '/leave-requests/{leaveRequest:uuid}', 'show'
      )->name('admin.leave-requests.show');

      Route::put(
        'leave-requests/{leaveRequest}/approve', 'approve'
      )->name('admin.leave-requests.approve');

      Route::put(
        'leave-requests/{leaveRequest}/reject', 'reject'
      )->name('admin.leave-requests.reject');

    });

  Route::controller(\App\Http\Controllers\Admin\DashboardController::class)->group(function () {
    Route::get(
      'dashboard', 'index'
    )->name('admin.dashboard');
  });

  Route::get('/calendar', function () {
    return \Inertia\Inertia::render('admin/Calendar');
  });

});
