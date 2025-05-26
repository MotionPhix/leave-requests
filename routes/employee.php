<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth'], function () {

  Route::controller(\App\Http\Controllers\Employee\LeaveRequestController::class)->group(function () {

    Route::get(
      '/leave-requests', 'index'
    )->name('leave-requests.index');

    Route::get(
      '/leave-requests/create', 'create'
    )->name('leave-requests.create');

    Route::post(
      '/leave-requests', 'store'
    )->name('leave-requests.store');

  });

  Route::controller(\App\Http\Controllers\Employee\DashboardController::class)->group(function () {
    Route::get(
      'dashboard', 'index'
    )->name('dashboard');
  });

  Route::get('/calendar', function () {
    return \Inertia\Inertia::render('employee/Calendar');
  })->name('calendar');

});
