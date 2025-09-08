<?php

use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WorkspaceSelectionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// =====================================
// CENTRAL SYSTEM ROUTES
// =====================================
// These routes handle login, registration, workspace selection
// and other global application features

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Invitation acceptance route (public)
Route::get('/invitation/{workspace}/{token}', InvitationAcceptController::class)
    ->name('invitations.accept')
    ->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::controller(NotificationController::class)->group(function () {
        Route::get('/notifications', 'index')->name('notifications.index');
    });

    // Central workspace management (this is the main workspace interface)
    Route::controller(WorkspaceSelectionController::class)->group(function () {
        Route::get('/workspaces', 'index')->name('workspaces.index');
        Route::get('/workspaces/create', 'create')->name('workspaces.create');
        Route::post('/workspaces', 'store')->name('workspaces.store');
        Route::get('/workspaces/{slug}/{uuid}', 'switch')->name('workspaces.switch');
    });
});

// Authentication routes
require __DIR__.'/auth.php';

// =====================================
// TENANT-SPECIFIC ROUTES
// =====================================
// These routes are prefixed with /{tenant:slug}/tenant:{uuid}
// and contain all tenant-specific functionality
require __DIR__.'/tenant.php';
