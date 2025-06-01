<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveType\StoreLeaveTypeRequest;
use App\Http\Requests\LeaveType\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Inertia\Inertia;

class LeaveTypeController extends Controller
{
  public function index()
  {
    return Inertia::render('admin/leave-types/Index', [
      'leaveTypes' => LeaveType::query()
        ->orderBy('name')
        ->get()
        ->map(fn($type) => [
          'id' => $type->id,
          'uuid' => $type->uuid,
          'name' => $type->name,
          'max_days_per_year' => $type->max_days_per_year,
          'requires_documentation' => $type->requires_documentation,
          'gender_specific' => $type->gender_specific,
          'gender' => $type->gender,
          'frequency_years' => $type->frequency_years,
          'pay_percentage' => $type->pay_percentage,
        ])
    ]);
  }

  public function create()
  {
    return Inertia::render('admin/leave-types/Create');
  }

  public function store(StoreLeaveTypeRequest $request)
  {
    LeaveType::create($request->validated());

    return redirect()->route('admin.leave-types.index')
      ->with('success', 'Leave type created successfully');
  }

  public function edit(LeaveType $leaveType)
  {
    return Inertia::render('admin/leave-types/Edit', [
      'leaveType' => [
        'id' => $leaveType->id,
        'name' => $leaveType->name,
        'description' => $leaveType->description,
        'max_days_per_year' => $leaveType->max_days_per_year,
        'requires_documentation' => $leaveType->requires_documentation,
        'gender_specific' => $leaveType->gender_specific,
        'gender' => $leaveType->gender,
        'frequency_years' => $leaveType->frequency_years,
        'pay_percentage' => $leaveType->pay_percentage,
        'minimum_notice_days' => $leaveType->minimum_notice_days,
      ]
    ]);
  }

  public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType)
  {
    $leaveType->update($request->validated());

    return redirect()->route('admin.leave-types.index')
      ->with('success', 'Leave type updated successfully');
  }

  public function show(LeaveType $leaveType, LeaveBalanceService $leaveService)
  {
    $stats = [
      'total_requests' => $leaveType->leaveRequests()->count(),
      'approved_requests' => $leaveType->leaveRequests()->where('status', 'approved')->count(),
      'pending_requests' => $leaveType->leaveRequests()->where('status', 'pending')->count(),
      'rejected_requests' => $leaveType->leaveRequests()->where('status', 'rejected')->count(),
      'total_days_taken' => $leaveType->leaveRequests()
        ->where('status', 'approved')
        ->get()
        ->sum(function ($request) {
          return $this->calculateWorkingDays($request->start_date, $request->end_date);
        }),
    ];

    $monthlyStats = $leaveType->leaveRequests()
      ->where('status', 'approved')
      ->whereYear('created_at', now()->year)
      ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
      ->groupBy('month')
      ->get()
      ->pluck('total', 'month')
      ->toArray();

    $employeeStats = $leaveType->leaveRequests()
      ->with('user:id,name')
      ->where('status', 'approved')
      ->whereYear('created_at', now()->year)
      ->get()
      ->groupBy('user.name')
      ->map(function ($requests) {
        return $requests->sum(function ($request) {
          return $this->calculateWorkingDays($request->start_date, $request->end_date);
        });
      })
      ->toArray();

    return Inertia::render('admin/leave-types/Show', [
      'leaveType' => [
        'id' => $leaveType->id,
        'name' => $leaveType->name,
        'description' => $leaveType->description,
        'max_days_per_year' => $leaveType->max_days_per_year,
        'requires_documentation' => $leaveType->requires_documentation,
        'gender_specific' => $leaveType->gender_specific,
        'gender' => $leaveType->gender,
        'frequency_years' => $leaveType->frequency_years,
        'pay_percentage' => $leaveType->pay_percentage,
      ],
      'stats' => $stats,
      'monthlyStats' => $monthlyStats,
      'employeeStats' => $employeeStats,
    ]);
  }

  public function destroy(LeaveType $leaveType)
  {
    // Check if any leaves of this type exist
    if ($leaveType->leaveRequests()->exists()) {
      return back()->with('error', 'Cannot delete leave type that has been used');
    }

    $leaveType->delete();

    return redirect()->route('admin.leave-types.index')
      ->with('success', 'Leave type deleted successfully');
  }
}
