<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
  public function index()
  {
    return Inertia::render('admin/users/Index', [
      'users' => User::query()
        ->with('roles')
        ->orderBy('name')
        ->paginate(10)
        ->through(fn($user) => [
          'id' => $user->id,
          'uuid' => $user->uuid,
          'name' => $user->name,
          'email' => $user->email,
          'gender' => $user->gender,
          'role' => $user->roles->first()?->name,
          'created_at' => $user->created_at->format('M d, Y')
        ])
    ]);
  }

  public function create()
  {
    return Inertia::render('admin/users/Create', [
      'roles' => \Spatie\Permission\Models\Role::all()
        ->map(fn($role) => [
          'id' => $role->id,
          'name' => $role->name
        ]),
      'departments' => Department::all(['id', 'name']),
      'managers' => User::role(['Manager', 'Admin'])
        ->select(['id', 'name'])
        ->orderBy('name')
        ->get(),
      'employmentTypes' => EmploymentType::toArray(),
      'employmentStatuses' => EmploymentStatus::toArray(),
      'genders' => Gender::toArray()
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'email' => 'required|string|email|max:255|unique:users',
      'gender' => ['required', Rule::in(array_column(Gender::toArray(), 'id'))],
      'password' => ['required', Password::defaults()],
      'role_id' => 'required|exists:roles,id',
      'position' => 'required|string|max:255',
      'department' => 'required|exists:departments,id',
      'join_date' => 'required|date',
      'reporting_to' => 'required|exists:users,id',
      'work_phone' => 'nullable|string|max:20',
      'office_location' => 'nullable|string|max:255',
      'employment_status' => ['required', 'string', Rule::in(array_column(EmploymentStatus::toArray(), 'id'))],
      'employment_type' => ['required', 'string', Rule::in(array_column(EmploymentType::toArray(), 'id'))],
    ]);

    $user = User::create([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'gender' => $validated['gender'],
      'password' => bcrypt($validated['password']),
      'position' => $validated['position'],
      'department' => $validated['department'],
      'join_date' => $validated['join_date'],
      'reporting_to' => $validated['reporting_to'],
      'work_phone' => $validated['work_phone'],
      'office_location' => $validated['office_location'],
      'employment_status' => $validated['employment_status'],
      'employment_type' => $validated['employment_type'],
    ]);

    $role = \Spatie\Permission\Models\Role::find($validated['role_id']);
    $user->assignRole($role);

    return redirect()->route('admin.employees.index')
      ->with('success', 'User created successfully');
  }

  /**
   * Show the user profile with leave statistics and balances.
   *
   * @param User $user
   * @param LeaveBalanceService $leaveBalanceService
   * @return \Inertia\Response
   */
  public function show(User $user, LeaveBalanceService $leaveBalanceService)
  {
    $user->load('leaveRequests.leaveType', 'manager', 'departmentModel');

    $leaveStats = [
      'total_leaves' => $user->leaveRequests()->count(),
      'pending_leaves' => $user->leaveRequests()->where('status', 'pending')->count(),
      'approved_leaves' => $user->leaveRequests()->where('status', 'approved')->count(),
      'rejected_leaves' => $user->leaveRequests()->where('status', 'rejected')->count(),
    ];

    $leaveBalances = $user->availableLeaveTypes->map(function ($leaveType) use ($user, $leaveBalanceService) {
      $used = $leaveBalanceService->getUsedDays($user->id, $leaveType->id);
      $total = $leaveType->max_days_per_year;
      return [
        'type' => $leaveType->name,
        'used' => $used,
        'remaining' => $total - $used,
        'total' => $total,
      ];
    });

    $leaveHistory = $user->leaveRequests()
      ->with('leaveType')
      ->orderBy('created_at', 'desc')
      ->get()
      ->map(fn($leave) => [
        'id' => $leave->id,
        'type' => $leave->leaveType->name,
        'start_date' => $leave->start_date,
        'end_date' => $leave->end_date,
        'status' => $leave->status,
        'created_at' => $leave->created_at->format('M d, Y'),
        'total_days' => $leaveBalanceService->calculateWorkingDays(
          $leave->start_date,
          $leave->end_date
        ),
      ]);

    return Inertia::render('admin/users/Show', [
      'user' => [
        'uuid' => $user->uuid,
        'name' => $user->name,
        'email' => $user->email,
        'gender' => $user->gender,
        'position' => $user->position,
        'department' => $user->departmentModel?->name,
        'employee_id' => $user->employee_id,
        'join_date' => $user->join_date,
        'reporting_to' => $user->manager?->name,
        'work_phone' => $user->work_phone,
        'office_location' => $user->office_location,
        'employment_status' => $user->employment_status,
        'employment_type' => $user->employment_type,
      ],
      'leaveStats' => $leaveStats,
      'leaveBalances' => $leaveBalances,
      'leaveHistory' => $leaveHistory,
    ]);
  }

  public function edit(User $user)
  {
    return Inertia::render('admin/users/Edit', [
      'user' => [
        'id' => $user->id,
        'uuid' => $user->uuid,
        'name' => $user->name,
        'email' => $user->email,
        'gender' => $user->gender,
        'role_id' => $user->roles->first()?->id,
        'position' => $user->position,
        'department' => $user->department,
        'join_date' => $user->join_date?->format('Y-m-d'),
        'reporting_to' => $user->reporting_to,
        'work_phone' => $user->work_phone,
        'office_location' => $user->office_location,
        'employment_status' => $user->employment_status,
        'employment_type' => $user->employment_type,
      ],
      'roles' => \Spatie\Permission\Models\Role::all()
        ->map(fn($role) => [
          'id' => $role->id,
          'name' => $role->name
        ]),
      'departments' => Department::all(['id', 'name']),
      'managers' => User::role(['Manager', 'Admin'])
        ->select(['id', 'name'])
        ->orderBy('name')
        ->get(),
      'employmentTypes' => EmploymentType::toArray(),
      'employmentStatuses' => EmploymentStatus::toArray(),
      'genders' => Gender::toArray(),
    ]);
  }

  public function update(Request $request, User $user)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
      'gender' => ['required', Rule::in(array_column(Gender::toArray(), 'id'))],
      'password' => ['nullable', Password::defaults()],
      'role_id' => 'required|exists:roles,id',
      'position' => 'required|string|max:255',
      'department' => 'required|exists:departments,id',
      'join_date' => 'required|date',
      'reporting_to' => [
        'required',
        'exists:users,id',
        Rule::notIn([$user->id]) // Prevent self-reporting
      ],
      'work_phone' => 'nullable|string|max:20',
      'office_location' => 'nullable|string|max:255',
      'employment_status' => ['required', Rule::in(array_column(EmploymentStatus::toArray(), 'id'))],
      'employment_type' => ['required', Rule::in(array_column(EmploymentType::toArray(), 'id'))],
    ]);

    $userData = array_filter([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'gender' => $validated['gender'],
      'position' => $validated['position'],
      'department' => $validated['department'],
      'join_date' => $validated['join_date'],
      'reporting_to' => $validated['reporting_to'],
      'work_phone' => $validated['work_phone'],
      'office_location' => $validated['office_location'],
      'employment_status' => $validated['employment_status'],
      'employment_type' => $validated['employment_type'],
    ]);

    // Only update password if provided
    if (!empty($validated['password'])) {
      $userData['password'] = bcrypt($validated['password']);
    }

    $user->update($userData);

    // Update role
    $role = \Spatie\Permission\Models\Role::find($validated['role_id']);
    $user->syncRoles([$role]);

    // Clear user permissions cache
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    return redirect()->route('admin.employees.index')
      ->with('success', 'Employee updated successfully');
  }

  public function destroy(User $user)
  {
    $user->delete();

    return redirect()->route('admin.employees.index')
      ->with('success', 'User deleted successfully');
  }
}
