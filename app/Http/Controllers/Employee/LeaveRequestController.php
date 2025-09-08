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
use App\Models\Workspace;
use Thunk\Verbs\Facades\Verbs;
use App\Events\LeaveRequestSubmitted;

class LeaveRequestController extends Controller
{
  use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
    // Check if user has permission to create leave requests
    // Owners and Super Admins cannot request leave - they manage the company!
    if (auth()->user()->hasRole(['Owner', 'Super Admin'])) {
      abort(403, 'Owners and super admins cannot request leave. You manage the company - you don\'t need permission to take time off!');
    }
    
    // Also check for the specific permission
    if (!auth()->user()->can('leave-requests.create')) {
      abort(403, 'You do not have permission to create leave requests.');
    }
    
    $leaveBalanceService = app(LeaveBalanceService::class);
    $leaveSummary = $leaveBalanceService->getUserLeaveSummary(Auth::id());

    $user = auth()->user();

    // Get all leave types and filter them properly
    $leaveTypes = LeaveType::all()->filter(function ($type) use ($user, $leaveBalanceService) {
      // Filter out gender-specific leaves that don't match user's gender
      if ($type->gender_specific && $type->gender !== 'any' && $type->gender !== $user->gender) {
        return false;
      }

      // Check frequency restrictions (for maternity/paternity/special leaves)
      if ($type->frequency_years > 1) {
        $lastApprovedRequest = $user->leaveRequests()
          ->where('leave_type_id', $type->id)
          ->where('status', LeaveStatus::Approved->value)
          ->where('created_at', '>=', now()->subYears($type->frequency_years))
          ->exists();

        if ($lastApprovedRequest) {
          return false; // User has already taken this leave within the frequency period
        }
      }

      // Only show leave types that have remaining days (unless they allow negative balance)
      $remainingDays = $leaveBalanceService->getRemainingDays($user->id, $type->id);
      if (!$type->allow_negative_balance && $remainingDays <= 0) {
        return false;
      }

      return true;
    })->map(function ($type) use ($leaveBalanceService, $user) {
      return [
        'id' => $type->id,
        'name' => $type->name,
        'description' => $type->description,
        'requires_documentation' => $type->requires_documentation,
        'minimum_notice_days' => $type->minimum_notice_days,
        'gender_specific' => $type->gender_specific,
        'gender' => $type->gender,
        'frequency_years' => $type->frequency_years,
        'max_days_per_year' => $type->max_days_per_year,
        'available_days' => $leaveBalanceService->getRemainingDays($user->id, $type->id),
        'pay_percentage' => $type->pay_percentage,
        'allow_negative_balance' => $type->allow_negative_balance,
      ];
    })->values();

    // Get leave balances for all leave types (for the sidebar)
    $leaveBalances = LeaveType::all()->map(function ($leaveType) use ($leaveBalanceService) {
      return [
        'leave_type_id' => $leaveType->id,
        'leave_type_name' => $leaveType->name,
        'max_days' => $leaveType->max_days_per_year,
        'used_days' => $leaveBalanceService->getUsedDays(Auth::id(), $leaveType->id),
        'remaining_days' => $leaveBalanceService->getRemainingDays(Auth::id(), $leaveType->id),
      ];
    });

    return Inertia::render('employee/leave-requests/Create', [
      'leaveTypes' => $leaveTypes,
      'holidays' => $this->getUpcomingHolidays(),
      'leaveBalances' => $leaveBalances,
      'leaveSummary' => $leaveSummary,
      'canRequestLeave' => $leaveSummary['can_request_new_leave'],
      'blockingReason' => $leaveSummary['blocking_reason'],
      'activeRequests' => $leaveSummary['active_requests']->map(function ($request) {
        return [
          'id' => $request->id,
          'leave_type' => $request->leaveType->name,
          'start_date' => $request->start_date->format('M d, Y'),
          'end_date' => $request->end_date->format('M d, Y'),
          'status' => $request->status,
          'status_label' => $request->getStatusLabel(),
          'total_days' => $request->total_days,
        ];
      }),
    ]);
  }

  public function store(StoreLeaveRequest $request)
  {
    // Owners and Super Admins cannot request leave - they manage the company!
    if (auth()->user()->hasRole(['Owner', 'Super Admin'])) {
      return redirect()->back()
        ->with('error', 'Owners and super admins cannot request leave. You manage the company - you don\'t need permission to take time off!')
        ->withInput();
    }
    
    $leaveBalanceService = app(LeaveBalanceService::class);

    $canRequestResult = $leaveBalanceService->canRequestNewLeave(Auth::id());

    if (!$canRequestResult['can_request']) {
      return back()->with('error', $canRequestResult['reason'])->withInput();
    }

    // Calculate business days (excluding weekends)
    $daysRequested = $leaveBalanceService->calculateWorkingDays(
      $request->start_date,
      $request->end_date
    );

    $canTake = $leaveBalanceService->hasSufficientBalance(
      $request->user()->id,
      $request->leave_type_id,
      $daysRequested
    );

    if (!$canTake) {
      return back()->with(
        'error',
        'Insufficient leave balance. You need ' . $daysRequested . ' days but only have ' .
        $leaveBalanceService->getRemainingDays(Auth::id(), $request->leave_type_id) . ' days remaining.'
      )->withInput();
    }

    // Check for overlapping leaves
    $hasOverlap = $leaveBalanceService->hasOverlappingLeave(
      Auth::id(),
      $request->start_date,
      $request->end_date
    );

    if ($hasOverlap) {
      return back()->with(
        'error',
        'You have overlapping leave requests for the selected dates. Please check your existing leave requests.'
      )->withInput();
    }

    $leaveType = \App\Models\LeaveType::findOrFail($request->leave_type_id);

    if ($leaveType->minimum_notice_days > 0) {
      $noticeDate = now()->addDays($leaveType->minimum_notice_days);
      $requestedStartDate = \Carbon\Carbon::parse($request->start_date);

      if ($requestedStartDate->lessThan($noticeDate)) {
        return back()->with(
          'error',
          "This leave type requires at least {$leaveType->minimum_notice_days} days notice. Please select a start date from {$noticeDate->format('M d, Y')} onwards."
        )->withInput();
      }
    }

    $leaveRequest = LeaveRequest::create([
      'user_id' => Auth::id(),
      'leave_type_id' => $request->leave_type_id,
      'start_date' => $request->start_date,
      'end_date' => $request->end_date,
      'reason' => $request->reason,
      'status' => LeaveStatus::Pending->value,
    ]);

    /*if ($request->hasFile('supporting_documents')) {
      foreach ($request->file('supporting_documents') as $file) {
        $leaveRequest->addMedia($file)
          ->toMediaCollection('supporting_documents');
      }
    }*/

    if ($request->hasFile('supporting_documents')) {
      foreach ($request->file('supporting_documents') as $file) {
        $leaveRequest->addMediaFromRequest('supporting_documents')
          ->each(function ($fileAdder) {
            $fileAdder->toMediaCollection('supporting_documents');
          });
      }
    }

    // Fire event for leave request submission - get workspace from context if available
    $workspace = $request->attributes->get('workspace');
    if ($workspace instanceof Workspace) {
      Verbs::fire(
        LeaveRequestSubmitted::fire($workspace, Auth::user(), $leaveRequest, $request->validated())
      );
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
        'comments' => $leaveRequest->comment,
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
