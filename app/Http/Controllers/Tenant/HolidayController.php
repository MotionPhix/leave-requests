<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Inertia\Inertia;
use Carbon\Carbon;

class HolidayController extends Controller
{
    /**
     * Display holidays list (role-based access)
     */
    public function index(Request $request): Response
    {
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        $userRole = $this->getUserRole($user, $workspace);
        
        // Apply role-based filtering
        $holidaysQuery = Holiday::query()
            ->where('workspace_id', $workspace->id)
            ->visibleToRole($userRole);
            
        // Add filters
        if ($request->filled('year')) {
            $year = $request->integer('year');
            $holidaysQuery->whereYear('start_date', $year);
        }
        
        if ($request->filled('type')) {
            $holidaysQuery->where('type', $request->string('type'));
        }
        
        $holidays = $holidaysQuery
            ->orderBy('start_date')
            ->get()
            ->map(fn($holiday) => [
                'id' => $holiday->id,
                'uuid' => $holiday->uuid,
                'name' => $holiday->name,
                'type' => $holiday->type,
                'date' => $holiday->start_date?->format('Y-m-d') ?? $holiday->date?->format('Y-m-d'),
                'start_date' => $holiday->start_date?->format('Y-m-d') ?? $holiday->date?->format('Y-m-d'),
                'end_date' => $holiday->end_date?->format('Y-m-d') ?? $holiday->date?->format('Y-m-d'),
                'date_range' => $holiday->date_range,
                'description' => $holiday->description,
                'is_recurring' => $holiday->is_recurring,
                'recurrence_pattern' => $holiday->recurrence_pattern,
                'color' => $holiday->color,
                'is_visible_to_employees' => $holiday->is_visible_to_employees,
                'duration' => $holiday->getDurationInDays(),
                'can_edit' => $userRole === 'owner',
                'can_delete' => $userRole === 'owner',
                'created_at' => $holiday->created_at?->toISOString(),
                'updated_at' => $holiday->updated_at?->toISOString(),
            ])
            ->toArray();

        $availableYears = Holiday::where('workspace_id', $workspace->id)
            ->selectRaw('YEAR(COALESCE(start_date, date)) as year')
            ->distinct()
            ->pluck('year')
            ->sort()
            ->values();

        return Inertia::render('tenant/holidays/Index', [
            'holidays' => $holidays,
            'workspace' => $workspace,
            'userRole' => $userRole,
            'canManageHolidays' => $userRole === 'owner',
            'availableYears' => $availableYears,
            'currentYear' => $request->integer('year', now()->year),
            'filters' => [
                'type' => $request->string('type'),
            ],
            'holidayTypes' => [
                'National Holiday',
                'Religious Holiday',
                'Company Holiday',
                'Floating Holiday',
                'Company Closure'
            ]
        ]);
    }

    /**
     * Show holiday creation form (Owner only)
     */
    public function create(Request $request): Response
    {
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        
        if ($this->getUserRole($user, $workspace) !== 'owner') {
            abort(403, 'Only workspace owners can create holidays');
        }

        $prefillDate = $request->input('start_date');
        
        return Inertia::render('tenant/holidays/Create', [
            'workspace' => $workspace,
            'prefillData' => $prefillDate ? ['start_date' => $prefillDate] : null,
            'holidayTypes' => [
                'National Holiday',
                'Religious Holiday', 
                'Company Holiday',
                'Floating Holiday',
                'Company Closure'
            ],
            'recurrencePatterns' => [
                'yearly' => 'Yearly',
                'monthly' => 'Monthly',
                'custom' => 'Custom'
            ]
        ]);
    }

    /**
     * Store a new holiday (Owner only)
     */
    public function store(Request $request)
    {
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        
        if ($this->getUserRole($user, $workspace) !== 'owner') {
            abort(403, 'Only workspace owners can create holidays');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => [
                'required',
                'string',
                Rule::in(['National Holiday', 'Religious Holiday', 'Company Holiday', 'Floating Holiday', 'Company Closure'])
            ],
            'description' => 'nullable|string|max:1000',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|string|in:yearly,monthly,custom',
            'is_visible_to_employees' => 'boolean'
        ]);

        // Check for conflicts with existing holidays
        $conflicts = $this->checkHolidayConflicts(
            $workspace,
            $validated['start_date'],
            $validated['end_date']
        );
        
        if ($conflicts->count() > 0) {
            return back()->withErrors([
                'date_conflict' => 'This date range conflicts with existing holidays: ' . 
                    $conflicts->pluck('name')->join(', ')
            ]);
        }

        // Create the holiday
        $validated['workspace_id'] = $workspace->id;
        $validated['date'] = $validated['start_date']; // Maintain backward compatibility
        
        $holiday = Holiday::create($validated);

        // If recurring, create future instances
        if ($validated['is_recurring'] && $validated['recurrence_pattern']) {
            $this->createRecurringHolidays($holiday, $validated['recurrence_pattern']);
        }

        $routeParams = [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
        ];

        return redirect()->route('tenant.management.holidays.index', $routeParams)
            ->with('success', 'Holiday created successfully');
    }

    /**
     * Show holiday edit form (Owner only)
     */
    public function edit(Request $request, string $tenant_slug, string $tenant_uuid, Holiday $holiday): Response
    {
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        
        if ($this->getUserRole($user, $workspace) !== 'owner') {
            abort(403, 'Only workspace owners can edit holidays');
        }

        if ($holiday->workspace_id !== $workspace->id) {
            abort(404);
        }

        return Inertia::render('tenant/holidays/Edit', [
            'holiday' => [
                'id' => $holiday->id,
                'uuid' => $holiday->uuid,
                'name' => $holiday->name,
                'start_date' => $holiday->start_date?->format('Y-m-d') ?? $holiday->date?->format('Y-m-d'),
                'end_date' => $holiday->end_date?->format('Y-m-d') ?? $holiday->date?->format('Y-m-d'),
                'type' => $holiday->type,
                'description' => $holiday->description,
                'color' => $holiday->color,
                'is_recurring' => $holiday->is_recurring,
                'recurrence_pattern' => $holiday->recurrence_pattern,
                'is_visible_to_employees' => $holiday->is_visible_to_employees,
            ],
            'workspace' => $workspace,
            'holidayTypes' => [
                'Public Holiday',
                'Company Holiday',
                'Religious Holiday', 
                'National Holiday',
                'Regional Holiday'
            ],
            'recurrencePatterns' => [
                'yearly' => 'Yearly',
                'monthly' => 'Monthly',
                'custom' => 'Custom'
            ]
        ]);
    }

    /**
     * Update holiday (Owner only)
     */
    public function update(Request $request, string $tenant_slug, string $tenant_uuid, Holiday $holiday)
    {
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        
        if ($this->getUserRole($user, $workspace) !== 'owner') {
            abort(403, 'Only workspace owners can update holidays');
        }

        if ($holiday->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => [
                'required',
                'string',
                Rule::in(['National Holiday', 'Religious Holiday', 'Company Holiday', 'Floating Holiday', 'Company Closure'])
            ],
            'description' => 'nullable|string|max:1000',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|string|in:yearly,monthly,custom',
            'is_visible_to_employees' => 'boolean'
        ]);

        // Check for conflicts excluding current holiday
        $conflicts = $this->checkHolidayConflicts(
            $workspace,
            $validated['start_date'],
            $validated['end_date'],
            $holiday->uuid
        );
        
        if ($conflicts->count() > 0) {
            return back()->withErrors([
                'date_conflict' => 'This date range conflicts with existing holidays: ' . 
                    $conflicts->pluck('name')->join(', ')
            ]);
        }

        $validated['date'] = $validated['start_date']; // Maintain backward compatibility
        $holiday->update($validated);

        $routeParams = [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
        ];

        return redirect()->route('tenant.management.holidays.index', $routeParams)
            ->with('success', 'Holiday updated successfully');
    }

    /**
     * Delete holiday (Owner only)
     */
    public function destroy(Request $request, string $tenant_slug, string $tenant_uuid, Holiday $holiday)
    {
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        
        if ($this->getUserRole($user, $workspace) !== 'owner') {
            abort(403, 'Only workspace owners can delete holidays');
        }

        if ($holiday->workspace_id !== $workspace->id) {
            abort(404);
        }

        $holiday->delete();

        $routeParams = [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
        ];

        return redirect()->route('tenant.management.holidays.index', $routeParams)
            ->with('success', 'Holiday deleted successfully');
    }

    /**
     * Get holiday impact analysis (shows affected leave requests)
     */
    public function impact(Request $request, string $tenant_slug, string $tenant_uuid, Holiday $holiday): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        
        if ($this->getUserRole($user, $workspace) !== 'owner') {
            abort(403);
        }

        if ($holiday->workspace_id !== $workspace->id) {
            abort(404);
        }

        // Find leave requests that overlap with this holiday
        $affectedRequests = LeaveRequest::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', '!=', 'rejected')
            ->inDateRange($holiday->start_date, $holiday->end_date)
            ->with(['user', 'leaveType'])
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->uuid,
                    'user' => $leave->user->name,
                    'leave_type' => $leave->leaveType->name,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'status' => $leave->status,
                    'overlap_days' => $this->calculateOverlapDays($leave, $holiday),
                ];
            });

        return response()->json([
            'affected_requests' => $affectedRequests,
            'total_affected' => $affectedRequests->count(),
            'warning_message' => $affectedRequests->count() > 0 
                ? 'This holiday overlaps with ' . $affectedRequests->count() . ' leave request(s)'
                : null
        ]);
    }

    /**
     * Bulk operations for holidays (Owner only)
     */
    public function bulk(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        
        if ($this->getUserRole($user, $workspace) !== 'owner') {
            abort(403);
        }

        $validated = $request->validate([
            'action' => 'required|string|in:delete,toggle_visibility,change_color',
            'holiday_ids' => 'required|array|min:1',
            'holiday_ids.*' => 'required|string|exists:holidays,uuid',
            'color' => 'required_if:action,change_color|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_visible_to_employees' => 'required_if:action,toggle_visibility|boolean'
        ]);

        $holidays = Holiday::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('uuid', $validated['holiday_ids'])
            ->get();

        $updated = 0;
        
        foreach ($holidays as $holiday) {
            switch ($validated['action']) {
                case 'delete':
                    $holiday->delete();
                    $updated++;
                    break;
                    
                case 'toggle_visibility':
                    $holiday->update(['is_visible_to_employees' => $validated['is_visible_to_employees']]);
                    $updated++;
                    break;
                    
                case 'change_color':
                    $holiday->update(['color' => $validated['color']]);
                    $updated++;
                    break;
            }
        }

        return response()->json([
            'message' => "Successfully {$validated['action']} {$updated} holiday(s)",
            'updated_count' => $updated
        ]);
    }

    /**
     * Get user role in workspace context
     */
    private function getUserRole(User $user, $workspace): string
    {
        if ($user->isOwner || $user->workspaces()->where('workspace_id', $workspace->id)->wherePivot('role', 'owner')->exists()) {
            return 'owner';
        }

        $roles = $user->roles()->wherePivot('tenant_id', $workspace->id)->pluck('name')->map(fn($role) => strtolower($role));

        if ($roles->contains('manager')) return 'manager';
        if ($roles->contains('hr') || $roles->contains('human resources')) return 'hr';
        
        return 'employee';
    }

    /**
     * Check for holiday conflicts in the given date range
     */
    private function checkHolidayConflicts($workspace, string $startDate, string $endDate, ?string $excludeUuid = null)
    {
        return Holiday::query()
            ->where('workspace_id', $workspace->id)
            ->when($excludeUuid, fn($query) => $query->where('uuid', '!=', $excludeUuid))
            ->inDateRange($startDate, $endDate)
            ->get();
    }

    /**
     * Create recurring holiday instances
     */
    private function createRecurringHolidays(Holiday $baseHoliday, string $pattern)
    {
        $startDate = Carbon::parse($baseHoliday->start_date);
        $endDate = Carbon::parse($baseHoliday->end_date);
        $duration = $startDate->diffInDays($endDate);
        
        // Create recurring holidays for the next 5 years
        for ($year = 1; $year <= 5; $year++) {
            $newStartDate = match ($pattern) {
                'yearly' => $startDate->copy()->addYears($year),
                'monthly' => $startDate->copy()->addMonths($year),
                default => null
            };
            
            if ($newStartDate) {
                $newEndDate = $newStartDate->copy()->addDays($duration);
                
                Holiday::create([
                    'workspace_id' => $baseHoliday->workspace_id,
                    'name' => $baseHoliday->name,
                    'start_date' => $newStartDate,
                    'end_date' => $newEndDate,
                    'date' => $newStartDate, // Backward compatibility
                    'type' => $baseHoliday->type,
                    'description' => $baseHoliday->description,
                    'color' => $baseHoliday->color,
                    'is_recurring' => false, // Don't create recursive recurring holidays
                    'recurrence_pattern' => null,
                    'is_visible_to_employees' => $baseHoliday->is_visible_to_employees,
                ]);
            }
        }
    }

    /**
     * Calculate overlap days between leave request and holiday
     */
    private function calculateOverlapDays(LeaveRequest $leave, Holiday $holiday): int
    {
        $leaveStart = Carbon::parse($leave->start_date);
        $leaveEnd = Carbon::parse($leave->end_date);
        $holidayStart = Carbon::parse($holiday->start_date ?? $holiday->date);
        $holidayEnd = Carbon::parse($holiday->end_date ?? $holiday->date);
        
        $overlapStart = $leaveStart->max($holidayStart);
        $overlapEnd = $leaveEnd->min($holidayEnd);
        
        return $overlapStart->lte($overlapEnd) ? $overlapStart->diffInDays($overlapEnd) + 1 : 0;
    }
}
