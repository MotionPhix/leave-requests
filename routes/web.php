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

  // Central workspace management
  Route::controller(\App\Http\Controllers\WorkspaceController::class)->group(function () {
    Route::get('/workspaces', 'index')->name('workspaces.index');
    Route::post('/workspaces', 'store')->name('workspaces.store');
    Route::get('/workspaces/open/{slug}/{uuid}', 'open')->name('workspaces.open');
  });
});

require __DIR__ . '/admin.php';
require __DIR__ . '/employee.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/tenant.php';
