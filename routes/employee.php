<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Inertia\Inertia;

Route::group(['middleware' => 'auth'], function () {

  Route::controller(\App\Http\Controllers\Employee\LeaveRequestController::class)->group(function () {

    Route::get(
      '/leave-requests',
      'index'
    )->name('leave-requests.index');

    Route::get(
      '/leave-requests/create',
      'create'
    )->name('leave-requests.create');

    Route::get(
      '/leave-requests/s/{leaveRequest}',
      'show'
    )->name('leave-requests.show');

    Route::post(
      '/leave-requests',
      'store'
    )->name('leave-requests.store')
      ->middleware([HandlePrecognitiveRequests::class]);
  });

  Route::controller(\App\Http\Controllers\Employee\DashboardController::class)->group(function () {
    Route::get(
      'dashboard',
      'index'
    )->name('dashboard');
  });

  Route::get('/calendar', function () {
    return Inertia::render('employee/Calendar');
  })->name('calendar');

  Route::prefix('settings')->group(function () {
    Route::redirect('', '/settings/profile');

    Route::controller(\App\Http\Controllers\Employee\Settings\ProfileController::class)->group(function () {
      Route::get('/profile', 'edit')->name('profile.edit');
      Route::patch('/profile', 'update')->name('profile.update');
      Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    Route::controller(\App\Http\Controllers\Employee\Settings\PasswordController::class)->group(function () {
      Route::get('/password', 'edit')->name('password.edit');
      Route::put('/password', 'update')->name('password.update');
    });

    Route::get('/appearance', function () {
      return Inertia::render('employee/settings/Appearance');
    })->name('appearance');
  });
});
