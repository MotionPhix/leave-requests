<?php

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
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
        '/leave-requests/s/{leaveRequest}',
        'show'
      )->name('admin.leave-requests.show');

      Route::post(
        'leave-requests/u/{leaveRequest:uuid}',
        'update'
      )->name('admin.leave-requests.update-status');
    });

  Route::prefix('leave-types')->group(function () {

    Route::controller(\App\Http\Controllers\Admin\LeaveTypeController::class)->group(function () {
      Route::get(
        '/', 'index'
      )->name('admin.leave-types.index');

      Route::get(
        '/create', 'create'
      )->name('admin.leave-types.create');

      Route::post(
        '/', 'store'
      )->name('admin.leave-types.store')
        ->middleware(HandlePrecognitiveRequests::class);

      Route::get(
        '/e/{leaveType:uuid}', 'edit'
      )->name('admin.leave-types.edit');

      Route::get(
        '/s/{leaveType:uuid}', 'show'
      )->name('admin.leave-types.show');

      Route::put(
        '/u/{leaveType}', 'update'
      )->name('admin.leave-types.update')
        ->middleware(HandlePrecognitiveRequests::class);

      Route::delete(
        '/d/{leaveType:uuid}', 'destroy'
      )->name('admin.leave-types.destroy');
    });

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

    Route::controller(\App\Http\Controllers\Admin\Settings\ActiveSessionController::class)->group(function () {
      Route::get('/sessions', 'index')->name('admin.sessions.index');
      Route::get('/sessions/new', 'create')->name('admin.sessions.create');
      Route::post('/sessions', 'store')->name('admin.sessions.store');
    });

    Route::get('/appearance', function () {
      return Inertia::render('admin/settings/Appearance');
    })->name('appearance');
  });

  Route::prefix('holidays')->group(function () {

    Route::controller(\App\Http\Controllers\Admin\HolidayController::class)->group(function () {
      Route::get('/', 'index')->name('admin.holidays.index');

      Route::get('/create', 'create')->name('admin.holidays.create');

      Route::post('/', 'store')
        ->name('admin.holidays.store')
        ->middleware(HandlePrecognitiveRequests::class);

      Route::get('/{holiday:uuid}/edit', 'edit')->name('admin.holidays.edit');

      Route::put('/{holiday}', 'update')
        ->name('admin.holidays.update')
        ->middleware(HandlePrecognitiveRequests::class);

      Route::delete('/{holiday}', 'destroy')->name('admin.holidays.destroy');
    });
  });

  Route::prefix('employees')->group(function () {

    Route::controller(\App\Http\Controllers\Admin\UserController::class)->group(function () {
      Route::get('/create', 'create')->name('admin.employees.create');

      Route::post('/', 'store')
        ->name('admin.employees.store')
        ->middleware(HandlePrecognitiveRequests::class);

      Route::get('/s/{user:uuid}', 'show')->name('admin.employees.show');

      Route::get('/e/{user:uuid}', 'edit')->name('admin.employees.edit');

      Route::put('/{user}', 'update')
        ->name('admin.employees.update')
        ->middleware(HandlePrecognitiveRequests::class);

      Route::delete('/{user:uuid}', 'destroy')->name('admin.employees.destroy');

      Route::get('/', 'index')->name('admin.employees.index');
    });
  });
});
