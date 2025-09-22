<?php

use Illuminate\Support\Facades\Route;

// =====================================
// TENANT-SPECIFIC ROUTES
// =====================================
// All routes here are for workspace-specific functionality
// Pattern: /{tenant:slug}/tenant:{uuid}/feature

Route::prefix('{tenant_slug}/{tenant_uuid}')
    ->where([
        'tenant_slug' => '[a-z0-9-]+',
        'tenant_uuid' => '[a-f0-9-]{36}',
    ])
    ->middleware(['auth', 'workspace'])
    ->group(function () {
        // Tenant dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Tenant\DashboardController::class, 'index'])
            ->name('tenant.dashboard');

        // =====================================
        // MANAGEMENT ROUTES (Owner/Manager/HR)
        // =====================================
        Route::prefix('management')
            ->middleware(['role:Owner|Manager|HR'])
            ->group(function () {
                // Management Dashboard
                Route::get('/dashboard', [\App\Http\Controllers\Tenant\DashboardController::class, 'managementDashboard'])->name('tenant.management.dashboard');
                // Calendar view (management perspective)
                Route::prefix('calendar')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\Tenant\CalendarController::class, 'index'])->name('tenant.management.calendar.index');
                        Route::get('/events', [\App\Http\Controllers\Tenant\CalendarController::class, 'events'])->name('tenant.management.calendar.events');
                    });

                // Team Members Management
                Route::prefix('members')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\Tenant\MembersController::class, 'index'])->name('tenant.management.members.index');
                        Route::post('/', [\App\Http\Controllers\Tenant\MembersController::class, 'store'])->name('tenant.management.members.store');
                        Route::put('/{userUuid}', [\App\Http\Controllers\Tenant\MembersController::class, 'update'])->name('tenant.management.members.update');
                        Route::delete('/{userUuid}', [\App\Http\Controllers\Tenant\MembersController::class, 'destroy'])->name('tenant.management.members.destroy');
                    });

                // Invitations Management
                Route::get('members/invitations', [\App\Http\Controllers\Tenant\InvitationsController::class, 'index'])->name('tenant.management.invitations.index');
                Route::post('members/invitations', [\App\Http\Controllers\Tenant\InvitationsController::class, 'store'])->name('tenant.management.invitations.store');
                Route::delete('members/invitations/{invitationId}', [\App\Http\Controllers\Tenant\InvitationsController::class, 'destroy'])->name('tenant.management.invitations.destroy');
                Route::get('dashboard/invite-member', [\App\Http\Controllers\Tenant\InvitationsController::class, 'create'])->name('tenant.management.invite-member');

                // Leave Types Management
                Route::prefix('leave-types')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'index'])->name('tenant.management.leave-types.index');
                        Route::get('/create', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'create'])->name('tenant.management.leave-types.create');
                        Route::post('/', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'store'])->name('tenant.management.leave-types.store');
                        Route::get('/{leaveType:uuid}/edit', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'edit'])->name('tenant.management.leave-types.edit');
                        Route::put('/{leaveType}', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'update'])->name('tenant.management.leave-types.update');
                        Route::delete('/{leaveType}', [\App\Http\Controllers\Tenant\LeaveTypeController::class, 'destroy'])->name('tenant.management.leave-types.destroy');
                    });

                // Departments Management
                Route::prefix('departments')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\Tenant\DepartmentController::class, 'index'])->name('tenant.management.departments.index');
                        Route::get('/create', [\App\Http\Controllers\Tenant\DepartmentController::class, 'create'])->name('tenant.management.departments.create');
                        Route::post('/', [\App\Http\Controllers\Tenant\DepartmentController::class, 'store'])->name('tenant.management.departments.store');
                        Route::get('/{department:uuid}/edit', [\App\Http\Controllers\Tenant\DepartmentController::class, 'edit'])->name('tenant.management.departments.edit');
                        Route::put('/{department}', [\App\Http\Controllers\Tenant\DepartmentController::class, 'update'])->name('tenant.management.departments.update');
                        Route::delete('/{department}', [\App\Http\Controllers\Tenant\DepartmentController::class, 'destroy'])->name('tenant.management.departments.destroy');
                    });

                // Reports Management
                Route::prefix('reports')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\Tenant\ReportController::class, 'index'])->name('tenant.management.reports.index');
                        Route::get('/leave-summary', [\App\Http\Controllers\Tenant\ReportController::class, 'leaveSummary'])->name('tenant.management.reports.leave-summary');
                        Route::get('/employee-usage', [\App\Http\Controllers\Tenant\ReportController::class, 'employeeUsage'])->name('tenant.management.reports.employee-usage');
                        Route::get('/department-analysis', [\App\Http\Controllers\Tenant\ReportController::class, 'departmentAnalysis'])->name('tenant.management.reports.department-analysis');
                    });

                // Holidays Management
                Route::prefix('holidays')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\Tenant\HolidayController::class, 'index'])->name('tenant.management.holidays.index');
                        Route::get('/create', [\App\Http\Controllers\Tenant\HolidayController::class, 'create'])->name('tenant.management.holidays.create');
                        Route::post('/', [\App\Http\Controllers\Tenant\HolidayController::class, 'store'])->name('tenant.management.holidays.store');
                        Route::get('/{holiday:uuid}/edit', [\App\Http\Controllers\Tenant\HolidayController::class, 'edit'])->name('tenant.management.holidays.edit');
                        Route::put('/{holiday:uuid}', [\App\Http\Controllers\Tenant\HolidayController::class, 'update'])->name('tenant.management.holidays.update');
                        Route::delete('/{holiday:uuid}', [\App\Http\Controllers\Tenant\HolidayController::class, 'destroy'])->name('tenant.management.holidays.destroy');
                        Route::get('/{holiday:uuid}/impact', [\App\Http\Controllers\Tenant\HolidayController::class, 'impact'])->name('tenant.management.holidays.impact');
                        Route::post('/bulk', [\App\Http\Controllers\Tenant\HolidayController::class, 'bulk'])->name('tenant.management.holidays.bulk');
                    });

                // Events Management (Owner/Manager/HR only)
                Route::prefix('events')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\Tenant\EventController::class, 'index'])->name('tenant.management.events.index');
                        Route::get('/create', [\App\Http\Controllers\Tenant\EventController::class, 'create'])->name('tenant.management.events.create');
                        Route::post('/', [\App\Http\Controllers\Tenant\EventController::class, 'store'])->name('tenant.management.events.store');
                        Route::get('/{event:uuid}', [\App\Http\Controllers\Tenant\EventController::class, 'show'])->name('tenant.management.events.show');
                        Route::get('/{event:uuid}/edit', [\App\Http\Controllers\Tenant\EventController::class, 'edit'])->name('tenant.management.events.edit');
                        Route::put('/{event:uuid}', [\App\Http\Controllers\Tenant\EventController::class, 'update'])->name('tenant.management.events.update');
                        Route::delete('/{event:uuid}', [\App\Http\Controllers\Tenant\EventController::class, 'destroy'])->name('tenant.management.events.destroy');
                    });

                // Leave Requests Management (approve/reject/view all)
                Route::prefix('leave-requests')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\Tenant\TenantLeaveRequestController::class, 'management'])->name('tenant.management.leave-requests.index');
                        Route::get('/{leaveRequest}', [\App\Http\Controllers\Tenant\TenantLeaveRequestController::class, 'showForManagement'])->name('tenant.management.leave-requests.show');
                        Route::patch('/{leaveRequest}', [\App\Http\Controllers\Tenant\TenantLeaveRequestController::class, 'updateStatus'])->name('tenant.management.leave-requests.update');
                    });
            });

        // =====================================
        // OWNER-ONLY ROUTES
        // =====================================
        Route::middleware(['role:Owner'])
            ->group(function () {
                // Note: Roles and Permissions are now handled via management.php routes

                // Settings (Owner only)
                Route::prefix('management/settings')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\Tenant\SettingsController::class, 'index'])->name('tenant.management.settings.index');
                        Route::put('/', [\App\Http\Controllers\Tenant\SettingsController::class, 'update'])->name('tenant.management.settings.update');
                    });
            });

        // =====================================
        // EMPLOYEE ROUTES (All workspace members)
        // =====================================
        // Leave requests for employees (their own requests)
        Route::prefix('leave-requests')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\LeaveRequestController::class, 'index'])->name('tenant.leave-requests.index');
                Route::get('/create', [\App\Http\Controllers\Tenant\LeaveRequestController::class, 'create'])->name('tenant.leave-requests.create');
                Route::post('/', [\App\Http\Controllers\Tenant\LeaveRequestController::class, 'store'])->name('tenant.leave-requests.store');
                Route::get('/{leaveRequest}', [\App\Http\Controllers\Tenant\LeaveRequestController::class, 'show'])->name('tenant.leave-requests.show');
                Route::patch('/{leaveRequest}/cancel', [\App\Http\Controllers\Tenant\LeaveRequestController::class, 'cancel'])->name('tenant.leave-requests.cancel');
            });

        // Events for employees (read-only access)
        Route::prefix('events')
            ->group(function () {
                Route::get('/{event:uuid}', [\App\Http\Controllers\Tenant\EventController::class, 'show'])->name('tenant.events.show');
            });

        // Team members (view-only for regular employees, can be accessed by all)
        Route::get('members', [\App\Http\Controllers\Tenant\MembersController::class, 'index'])->name('tenant.members.index');
    });
