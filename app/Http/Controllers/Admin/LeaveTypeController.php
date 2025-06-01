<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;
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

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255|unique:leave_types',
      'description' => 'nullable|string',
      'max_days_per_year' => 'required|integer|min:0',
      'requires_documentation' => 'boolean',
      'gender_specific' => 'boolean',
      'gender' => 'required|in:male,female,any',
      'frequency_years' => 'required|integer|min:1',
      'pay_percentage' => 'required|numeric|min:0|max:100',
      'minimum_notice_days' => 'required|integer|min:0',
    ]);

    LeaveType::create($validated);

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

  public function update(Request $request, LeaveType $leaveType)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255|unique:leave_types,name,' . $leaveType->id,
      'description' => 'nullable|string',
      'max_days_per_year' => 'required|integer|min:0',
      'requires_documentation' => 'boolean',
      'gender_specific' => 'boolean',
      'gender' => 'required|in:male,female,any',
      'frequency_years' => 'required|integer|min:1',
      'pay_percentage' => 'required|numeric|min:0|max:100',
      'minimum_notice_days' => 'required|integer|min:0',
    ]);

    $leaveType->update($validated);

    return redirect()->route('admin.leave-types.index')
      ->with('success', 'Leave type updated successfully');
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
