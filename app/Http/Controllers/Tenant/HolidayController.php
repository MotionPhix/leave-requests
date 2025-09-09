<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;

class HolidayController extends Controller
{
    public function index(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        $holidays = Holiday::query()
            ->where('workspace_id', $workspace->id)
            ->orderBy('date')
            ->paginate(10)
            ->through(fn($holiday) => [
                'id' => $holiday->id,
                'uuid' => $holiday->uuid,
                'name' => $holiday->name,
                'type' => $holiday->type,
                'date' => $holiday->date->format('Y-m-d'),
                'description' => $holiday->description,
                'is_recurring' => $holiday->is_recurring,
            ]);

        return Inertia::render('tenant/holidays/Index', [
            'holidays' => $holidays,
            'workspace' => $workspace,
        ]);
    }

    public function create(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        return Inertia::render('tenant/holidays/Create', [
            'workspace' => $workspace,
        ]);
    }

    public function store(Request $request, string $tenant_slug, string $tenant_uuid)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|string|in:Public Holiday,Company Holiday',
            'is_recurring' => 'boolean',
            'description' => 'nullable|string'
        ]);

        $validated['workspace_id'] = $workspace->id;

        Holiday::create($validated);

        return redirect()->route('tenant.holidays.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Holiday created successfully');
    }

    public function edit(Request $request, string $tenant_slug, string $tenant_uuid, Holiday $holiday): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the holiday belongs to this workspace
        if ($holiday->workspace_id !== $workspace->id) {
            abort(404);
        }

        return Inertia::render('tenant/holidays/Edit', [
            'holiday' => $holiday,
            'workspace' => $workspace,
        ]);
    }

    public function update(Request $request, string $tenant_slug, string $tenant_uuid, Holiday $holiday)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the holiday belongs to this workspace
        if ($holiday->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|string|in:Public Holiday,Company Holiday',
            'is_recurring' => 'boolean',
            'description' => 'nullable|string'
        ]);

        $holiday->update($validated);

        return redirect()->route('tenant.holidays.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Holiday updated successfully');
    }

    public function destroy(Request $request, string $tenant_slug, string $tenant_uuid, Holiday $holiday)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the holiday belongs to this workspace
        if ($holiday->workspace_id !== $workspace->id) {
            abort(404);
        }

        $holiday->delete();

        return redirect()->route('tenant.holidays.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Holiday deleted successfully');
    }
}
