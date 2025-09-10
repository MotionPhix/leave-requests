<?php

namespace App\Http\Controllers\Tenant;

use App\Events\LeaveRequestApproved;
use App\Events\LeaveRequestRejected;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Workspace;
use App\Notifications\LeaveRequestUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Thunk\Verbs\Facades\Verbs;

class LeaveRequestController extends Controller
{
    /**
     * Management view of all leave requests in the workspace
     */
    public function management(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        $query = LeaveRequest::with(['leaveType', 'user'])
            ->where('workspace_id', $workspace->id)
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                return $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->when($request->type, function ($query, $type) {
                return $query->where('leave_type_id', $type);
            })
            ->latest();

        $leaveRequests = $query->paginate(10)->withQueryString();
        $leaveTypes = LeaveType::where('workspace_id', $workspace->id)->get();

        return Inertia::render('tenant/leave-requests/Management', [
            'leaveRequests' => $leaveRequests,
            'leaveTypes' => $leaveTypes,
            'filters' => $request->only(['search', 'status', 'type']),
            'workspace' => $workspace,
        ]);
    }

    /**
     * Show a specific leave request for management
     */
    public function showForManagement(Request $request, string $tenant_slug, string $tenant_uuid, LeaveRequest $leaveRequest): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the leave request belongs to this workspace
        if ($leaveRequest->workspace_id !== $workspace->id) {
            abort(404);
        }

        $leaveRequest->load(['leaveType', 'user']);

        return Inertia::render('tenant/leave-requests/Show', [
            'leaveRequest' => $leaveRequest,
            'workspace' => $workspace,
        ]);
    }

    /**
     * Update leave request status (approve/reject)
     */
    public function updateStatus(Request $request, string $tenant_slug, string $tenant_uuid, LeaveRequest $leaveRequest)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the leave request belongs to this workspace
        if ($leaveRequest->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'manager_notes' => 'nullable|string|max:1000',
        ]);

        $leaveRequest->update([
            'status' => $validated['status'],
            'manager_notes' => $validated['manager_notes'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // Fire appropriate event
        if ($validated['status'] === 'approved') {
            Verbs::fire(LeaveRequestApproved::class, $leaveRequest);
        } else {
            Verbs::fire(LeaveRequestRejected::class, $leaveRequest);
        }

        // Notify the employee
        $leaveRequest->user->notify(new LeaveRequestUpdated($leaveRequest));

        return redirect()->route('tenant.management.leave-requests.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', "Leave request has been {$validated['status']}.");
    }

    /**
     * Employee view - create leave request
     */
    public function create(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();
        $leaveTypes = LeaveType::where('workspace_id', $workspace->id)->get();

        return Inertia::render('tenant/leave-requests/Create', [
            'leaveTypes' => $leaveTypes,
            'workspace' => $workspace,
        ]);
    }

    /**
     * Employee view - their own leave requests
     */
    public function index(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        $leaveRequests = LeaveRequest::with(['leaveType'])
            ->where('workspace_id', $workspace->id)
            ->where('user_id', Auth::id())
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('tenant/leave-requests/Index', [
            'leaveRequests' => $leaveRequests,
            'filters' => $request->only(['status']),
            'workspace' => $workspace,
        ]);
    }

    /**
     * Store a new leave request
     */
    public function store(Request $request, string $tenant_slug, string $tenant_uuid)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'emergency_contact' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['workspace_id'] = $workspace->id;
        $validated['status'] = 'pending';

        LeaveRequest::create($validated);

        return redirect()->route('tenant.leave-requests.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Leave request submitted successfully.');
    }

    /**
     * Show a specific leave request for the requesting employee
     */
    public function show(Request $request, string $tenant_slug, string $tenant_uuid, LeaveRequest $leaveRequest): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the leave request belongs to this workspace and user
        if ($leaveRequest->workspace_id !== $workspace->id || $leaveRequest->user_id !== Auth::id()) {
            abort(404);
        }

        $leaveRequest->load(['leaveType']);

        return Inertia::render('tenant/leave-requests/Show', [
            'leaveRequest' => $leaveRequest,
            'workspace' => $workspace,
        ]);
    }

    /**
     * Cancel a leave request
     */
    public function cancel(Request $request, string $tenant_slug, string $tenant_uuid, LeaveRequest $leaveRequest)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the leave request belongs to this workspace and user
        if ($leaveRequest->workspace_id !== $workspace->id || $leaveRequest->user_id !== Auth::id()) {
            abort(404);
        }

        // Only allow cancellation if status is pending
        if ($leaveRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'You can only cancel pending leave requests.']);
        }

        $leaveRequest->update(['status' => 'cancelled']);

        return redirect()->route('tenant.leave-requests.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Leave request has been cancelled.');
    }
}
