<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Inertia\Inertia;

Route::group(['middleware' => 'auth'], function () {

  Route::prefix('leave-requests')->name('leave-requests.')->group(function () {

    Route::controller(\App\Http\Controllers\Employee\LeaveRequestController::class)->group(function () {

      Route::get(
        '/create',
        'create'
      )->name('create');

      Route::post(
        '/c/{leaveRequest:uuid}',
        'cancel'
      )->name('cancel');

      Route::get(
        '/s/{leaveRequest:uuid}',
        'show'
      )->name('show');

      Route::get(
        '/',
        'index'
      )->name('index');

      Route::post(
        '/',
        'store'
      )->name('store')
        ->middleware([HandlePrecognitiveRequests::class]);
    });

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
