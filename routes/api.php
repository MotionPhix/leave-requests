<?php

use App\Http\Controllers\Employee\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function() {
  // Admin routes
  Route::get(
    '/admin/calendar',
    [\App\Http\Controllers\Admin\Api\CalendarController::class, 'index']
  )->name('api.admin.calendar');

  // Employee routes
  Route::get(
    '/calendar',
    [\App\Http\Controllers\Employee\Api\CalendarController::class, 'index']
  )->name('api.calendar');


  Route::get('/notifications', [NotificationController::class, 'index']);
  Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);

});
