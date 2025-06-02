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

  public function create(LeaveBalanceService $leaveBalanceService): Response
  {
    $user = auth()->user();

    // Get available leave types with balances
    $leaveTypes = LeaveType::all()->map(function ($type) use ($leaveBalanceService, $user) {
      return [
        'id' => $type->id,
        'name' => $type->name,
        'description' => $type->description,
        'requires_documentation' => $type->requires_documentation,
        'minimum_notice_days' => $type->minimum_notice_days,
        'gender_specific' => $type->gender_specific,
        'gender' => $type->gender,
        'frequency_years' => $type->frequency_years,
        'available_days' => $leaveBalanceService->getRemainingDays($user->id, $type->id),
        'pay_percentage' => $type->pay_percentage,
      ];
    })->filter(function ($type) use ($user) {
      // Filter out gender-specific leaves that don't match user's gender
      if ($type['gender_specific'] && $type['gender'] !== $user->gender) {
        return false;
      }
      return true;
    })->values();

    return Inertia::render('employee/leave-requests/Create', [
      'leaveTypes' => $leaveTypes,
      'holidays' => $this->getUpcomingHolidays(),
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
        'error',
        'Insufficient leave balance.'
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
        'error',
        'You have overlapping leave requests for the selected dates.'
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

    if ($request->hasFile('supporting_documents')) {
      foreach ($request->file('supporting_documents') as $file) {
        $leaveRequest->addMedia($file)
          ->toMediaCollection('supporting_documents');
      }
    }

    return redirect()->route('leave-requests.index')->with('success', 'Leave request submitted.');
  }

  public function show(LeaveRequest $leaveRequest)
  {
    if (auth()->user()->cannot('view', $leaveRequest)) {
      return back()->with('error', 'You do not have permission to view this leave request.');
    }

    $documentation = $leaveRequest->getFirstMedia('documentation');

    return Inertia::render('employee/leave-requests/Show', [
      'leaveRequest' => [
        'uuid' => $leaveRequest->uuid,
        'leave_type' => [
          'name' => $leaveRequest->leaveType->name,
          'requires_documentation' => $leaveRequest->leaveType->requires_documentation,
        ],
        'start_date' => $leaveRequest->start_date->format('Y-m-d'),
        'end_date' => $leaveRequest->end_date->format('Y-m-d'),
        'total_days' => $leaveRequest->total_days,
        'reason' => $leaveRequest->reason,
        'status' => $leaveRequest->status,
        'comments' => $leaveRequest->comments,
        'documentation' => $documentation ? [
          'name' => $documentation->name,
          'url' => $documentation->getUrl(),
        ] : null,
        'created_at' => $leaveRequest->created_at->format('Y-m-d H:i:s'),
        'updated_at' => $leaveRequest->updated_at->format('Y-m-d H:i:s'),
      ]
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

  public function cancel(Request $request, LeaveRequest $leaveRequest)
  {
    if (!$leaveRequest->canBeCancelled()) {
      return back()->with('error', 'This leave request cannot be cancelled.');
    }

    if ($leaveRequest->user_id !== auth()->id()) {
      return back()->with('error', 'You can only cancel your own leave requests.');
    }

    $request->validate([
      'reason' => 'nullable|string|max:500'
    ]);

    try {
      $leaveRequest->cancel($request->reason);
      return back()->with('success', 'Leave request cancelled successfully.');
    } catch (\Exception $e) {
      return back()->with('error', 'Failed to cancel leave request.');
    }
  }

  protected function getUpcomingHolidays(): array
  {
    return \App\Models\Holiday::where('date', '>=', now())
      ->where('date', '<=', now()->addMonths(3))
      ->orderBy('date')
      ->get()
      ->map(fn($holiday) => [
        'date' => $holiday->date,
        'name' => $holiday->name,
      ])
      ->toArray();
  }
}
