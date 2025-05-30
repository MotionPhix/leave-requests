<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::group([
  'prefix' => 'admin',
  'middleware' => ['auth', 'role:Admin|Manager|HR']
], function () {

  Route::controller(App\Http\Controllers\Admin\LeaveRequestController::class)
    ->group(function () {

      Route::get(
        '/leave-requests',
        'index'
      )->name('admin.leave-requests.index');

      Route::get(
        '/leave-requests/{leaveRequest:uuid}',
        'show'
      )->name('admin.leave-requests.show');

      Route::put(
        'leave-requests/{leaveRequest}/approve',
        'approve'
      )->name('admin.leave-requests.approve');

      Route::put(
        'leave-requests/{leaveRequest}/reject',
        'reject'
      )->name('admin.leave-requests.reject');
    });

  Route::controller(\App\Http\Controllers\Admin\DashboardController::class)->group(function () {
    Route::get(
      'dashboard',
      'index'
    )->name('admin.dashboard');
  });

  Route::get('/calendar', function () {
    return \Inertia\Inertia::render('admin/Calendar');
  });

  Route::prefix('settings')->group(function () {
    Route::redirect('', '/admin/settings/profile');

    Route::controller(\App\Http\Controllers\Admin\Settings\ProfileController::class)->group(function () {
      Route::get('/profile', 'edit')->name('admin.profile.edit');
      Route::patch('/profile', 'update')->name('admin.profile.update');
      Route::delete('/profile', 'destroy')->name('admin.profile.destroy');
    });

    Route::controller(\App\Http\Controllers\Admin\Settings\PasswordController::class)->group(function () {
      Route::get('/password', 'edit')->name('admin.password.edit');
      Route::put('/password', 'update')->name('admin.password.update');
    });

    Route::get('/appearance', function () {
      return Inertia::render('admin/settings/Appearance');
    })->name('appearance');
  });

});
