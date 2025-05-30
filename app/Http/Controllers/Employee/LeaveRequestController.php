<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use App\Enums\LeaveStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
  public function index(Request $request): Response
  {
    $query = LeaveRequest::query()->with(['leaveType', 'user']);

    // Filters
    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    if ($request->filled('leave_type_id')) {
      $query->where('leave_type_id', $request->leave_type_id);
    }

    if ($request->filled('date_from') && $request->filled('date_to')) {
      $query->whereBetween('start_date', [$request->date_from, $request->date_to]);
    }

    if ($request->filled('date_from') && ! $request->filled('date_to')) {
      $query->where('start_date', '>=', $request->date_from);
    }

    // Role-based filtering
    $query->where('user_id', auth()->id());

    $requests = $query->latest()->paginate(10)->withQueryString();

    // For filters (leave types dropdown)
    $leaveTypes = \App\Models\LeaveType::all();

    return inertia('employee/leave-requests/Index', [
      'leaveRequests' => $requests,
      'leaveTypes' => $leaveTypes,
      'filters' => $request->only('status', 'leave_type_id', 'date_from', 'date_to')
    ]);
  }

  public function create(): Response
  {
    return Inertia::render('employee/leave-requests/Create', [
      'leaveTypes' => LeaveType::all(['id', 'name', 'max_days_per_year'])
    ]);
  }

  public function store(StoreLeaveRequest $request)
  {
    // Calculate business days (excluding weekends)
    $daysRequested = now()
      ->parse($request->start_date)
      ->diffInDaysFiltered(
        fn($date) => !$date->isWeekend(),
        now()->parse($request->end_date)
      ) + 1;

    $canTake = app(LeaveBalanceService::class)->hasSufficientBalance(
      Auth::id(),
      $request->leave_type_id,
      $daysRequested
    );

    if (!$canTake) {
      return back()->with(
        'error', 'Insufficient leave balance.'
      )->withInput();
    }

    // Check for overlapping leaves
    $hasOverlap = LeaveRequest::query()
      ->where('user_id', Auth::id())
      ->where('status', '!=', LeaveStatus::Rejected->value)
      ->where(function ($query) use ($request) {
          $query->whereBetween('start_date', [$request->start_date, $request->end_date])
              ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
      })
        ->exists();

    if ($hasOverlap) {
      return back()->with(
        'error', 'You have overlapping leave requests for the selected dates.'
      )->withInput();
    }

    LeaveRequest::create([
      'user_id' => Auth::id(),
      'leave_type_id' => $request->leave_type_id,
      'start_date' => $request->start_date,
      'end_date' => $request->end_date,
      'reason' => $request->reason,
      'status' => LeaveStatus::Pending->value,
    ]);

    return redirect()->route('leave-requests.index')->with('success', 'Leave request submitted.');
  }

  public function show(LeaveRequest $leaveRequest): Response
  {
    $this->authorize('view', $leaveRequest);

    return Inertia::render('LeaveRequests/Show', [
      'leaveRequest' => $leaveRequest->load('leaveType', 'user'),
    ]);
  }

  public function update(Request $request, LeaveRequest $leaveRequest)
  {
    $this->authorize('approve', $leaveRequest);

    $request->validate([
      'status' => 'required|in:' . implode(',', LeaveStatus::cases()),
      'comment' => 'nullable|string|max:1000',
    ]);

    $leaveRequest->update([
      'status' => $request->status,
      'comment' => $request->comment,
      'approved_by' => Auth::id(),
    ]);

    return back()->with('success', 'Leave request updated.');
  }
}
