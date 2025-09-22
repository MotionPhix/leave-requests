<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            $workspace = $request->route('workspace') ?? Workspace::where('uuid', $request->route('tenant_uuid'))->first();
            
            if (!$user || !$workspace) {
                abort(403, 'Unauthorized access.');
            }
            
            // For show method, all workspace members can view events
            if ($request->route()->getName() === 'tenant.events.show') {
                return $next($request);
            }
            
            // For management routes, only owners, managers, and HR can access
            if (!in_array($user->pivot->role ?? $user->role, ['owner', 'manager', 'hr'])) {
                abort(403, 'Only workspace owners, managers, and HR can manage events.');
            }
            
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $workspace = $request->workspace ?? Workspace::where('uuid', $request->route('tenant_uuid'))->firstOrFail();

        $events = Event::forWorkspace($workspace->id)
            ->with('creator:id,name,email')
            ->when($request->type, fn($query) => $query->ofType($request->type))
            ->when($request->start_date && $request->end_date, 
                fn($query) => $query->inDateRange($request->start_date, $request->end_date)
            )
            ->orderBy('start_date', 'desc')
            ->paginate(15);

        return Inertia::render('Tenant/Events/Index', [
            'workspace' => $workspace->only(['id', 'uuid', 'name', 'slug']),
            'events' => $events,
            'filters' => $request->only(['type', 'start_date', 'end_date']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $workspace = $request->workspace ?? Workspace::where('uuid', $request->route('tenant_uuid'))->firstOrFail();

        return Inertia::render('Tenant/Events/Create', [
            'workspace' => $workspace->only(['id', 'uuid', 'name', 'slug']),
            'defaultDate' => $request->date,
            'eventTypes' => $this->getEventTypes(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $workspace = $request->workspace ?? Workspace::where('uuid', $request->route('tenant_uuid'))->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in(['meeting', 'announcement', 'training', 'social', 'other'])],
            'location' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'all_day' => ['boolean'],
            'attendees' => ['nullable', 'array'],
            'attendees.*' => ['exists:users,id'],
            'is_mandatory' => ['boolean'],
        ]);

        $event = Event::create([
            ...$validated,
            'workspace_id' => $workspace->id,
            'created_by' => Auth::id(),
            'all_day' => $validated['all_day'] ?? true,
            'is_mandatory' => $validated['is_mandatory'] ?? false,
        ]);

        return redirect()->route('tenant.management.events.show', [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
            'event' => $event->uuid,
        ])->with('success', 'Event created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Event $event)
    {
        $workspace = $request->workspace ?? Workspace::where('uuid', $request->route('tenant_uuid'))->firstOrFail();

        // Ensure the event belongs to this workspace
        if ($event->workspace_id !== $workspace->id) {
            abort(404);
        }

        $event->load('creator:id,name,email');

        $userRole = Auth::user()->pivot->role ?? Auth::user()->role;
        $isManagement = in_array($userRole, ['owner', 'manager', 'hr']);
        
        return Inertia::render('Tenant/Events/Show', [
            'workspace' => $workspace->only(['id', 'uuid', 'name', 'slug']),
            'event' => $event,
            'canEdit' => $isManagement,
            'canDelete' => $isManagement,
            'isEmployee' => !$isManagement,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Event $event)
    {
        $workspace = $request->workspace ?? Workspace::where('uuid', $request->route('tenant_uuid'))->firstOrFail();

        // Ensure the event belongs to this workspace
        if ($event->workspace_id !== $workspace->id) {
            abort(404);
        }

        return Inertia::render('Tenant/Events/Edit', [
            'workspace' => $workspace->only(['id', 'uuid', 'name', 'slug']),
            'event' => $event,
            'eventTypes' => $this->getEventTypes(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $workspace = $request->workspace ?? Workspace::where('uuid', $request->route('tenant_uuid'))->firstOrFail();

        // Ensure the event belongs to this workspace
        if ($event->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in(['meeting', 'announcement', 'training', 'social', 'other'])],
            'location' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'all_day' => ['boolean'],
            'attendees' => ['nullable', 'array'],
            'attendees.*' => ['exists:users,id'],
            'is_mandatory' => ['boolean'],
        ]);

        $event->update([
            ...$validated,
            'all_day' => $validated['all_day'] ?? true,
            'is_mandatory' => $validated['is_mandatory'] ?? false,
        ]);

        return redirect()->route('tenant.management.events.show', [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
            'event' => $event->uuid,
        ])->with('success', 'Event updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Event $event)
    {
        $workspace = $request->workspace ?? Workspace::where('uuid', $request->route('tenant_uuid'))->firstOrFail();

        // Ensure the event belongs to this workspace
        if ($event->workspace_id !== $workspace->id) {
            abort(404);
        }

        $event->delete();

        return redirect()->route('tenant.management.events.index', [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
        ])->with('success', 'Event deleted successfully.');
    }

    /**
     * Get available event types
     */
    private function getEventTypes(): array
    {
        return [
            ['value' => 'meeting', 'label' => 'Meeting', 'color' => '#10b981'],
            ['value' => 'announcement', 'label' => 'Announcement', 'color' => '#3b82f6'],
            ['value' => 'training', 'label' => 'Training', 'color' => '#f59e0b'],
            ['value' => 'social', 'label' => 'Social Event', 'color' => '#ec4899'],
            ['value' => 'other', 'label' => 'Other', 'color' => '#6b7280'],
        ];
    }
}
