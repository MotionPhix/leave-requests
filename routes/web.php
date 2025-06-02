<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
  return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
  Route::controller(NotificationController::class)->group(function () {
    Route::get('/notifications', 'index')->name('notifications.index');
  });
});

require __DIR__ . '/admin.php';
require __DIR__ . '/employee.php';
require __DIR__ . '/auth.php';
