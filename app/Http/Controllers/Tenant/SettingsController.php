<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\EmployeeIdSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display the workspace settings
     */
    public function index(Request $request): Response
    {
        $workspace = $request->workspace;

        // Get employee ID settings for this workspace
        $employeeIdSetting = EmployeeIdSetting::where('workspace_id', $workspace->id)->first();

        return Inertia::render('tenant/settings/Index', [
            'workspace' => $workspace,
            'settings' => [
                'employee_id' => [
                    'prefix' => $employeeIdSetting?->prefix ?? 'EMP',
                    'start_number' => $employeeIdSetting?->start_number ?? 1000,
                    'length' => $employeeIdSetting?->length ?? 4,
                    'separator' => $employeeIdSetting?->separator ?? '-',
                ],
                'general' => [
                    'name' => $workspace->name,
                    'description' => $workspace->description,
                    'timezone' => $workspace->timezone ?? config('app.timezone'),
                    'date_format' => $workspace->date_format ?? 'Y-m-d',
                    'week_start' => $workspace->week_start ?? 1, // Monday
                ],
                'leave_policy' => [
                    'max_days_in_advance' => $workspace->max_leave_days_advance ?? 30,
                    'min_notice_days' => $workspace->min_leave_notice_days ?? 7,
                    'allow_weekend_requests' => $workspace->allow_weekend_requests ?? false,
                    'auto_approve_after_days' => $workspace->auto_approve_after_days ?? null,
                ],
            ],
        ]);
    }

    /**
     * Update workspace settings
     */
    public function update(Request $request)
    {
        $workspace = $request->workspace;

        $validated = $request->validate([
            // General settings
            'general.name' => 'required|string|max:255',
            'general.description' => 'nullable|string|max:1000',
            'general.timezone' => 'required|string|max:255',
            'general.date_format' => 'required|string|max:50',
            'general.week_start' => 'required|integer|min:0|max:6',

            // Employee ID settings
            'employee_id.prefix' => 'required|string|max:10',
            'employee_id.start_number' => 'required|integer|min:1',
            'employee_id.length' => 'required|integer|min:1|max:10',
            'employee_id.separator' => 'required|string|max:5',

            // Leave policy settings
            'leave_policy.max_days_in_advance' => 'required|integer|min:1|max:365',
            'leave_policy.min_notice_days' => 'required|integer|min:0|max:30',
            'leave_policy.allow_weekend_requests' => 'boolean',
            'leave_policy.auto_approve_after_days' => 'nullable|integer|min:1|max:30',
        ]);

        // Update workspace general settings
        $workspace->update([
            'name' => $validated['general']['name'],
            'description' => $validated['general']['description'],
            'timezone' => $validated['general']['timezone'],
            'date_format' => $validated['general']['date_format'],
            'week_start' => $validated['general']['week_start'],
            'max_leave_days_advance' => $validated['leave_policy']['max_days_in_advance'],
            'min_leave_notice_days' => $validated['leave_policy']['min_notice_days'],
            'allow_weekend_requests' => $validated['leave_policy']['allow_weekend_requests'],
            'auto_approve_after_days' => $validated['leave_policy']['auto_approve_after_days'],
        ]);

        // Update or create employee ID settings
        EmployeeIdSetting::updateOrCreate(
            ['workspace_id' => $workspace->id],
            [
                'prefix' => $validated['employee_id']['prefix'],
                'start_number' => $validated['employee_id']['start_number'],
                'length' => $validated['employee_id']['length'],
                'separator' => $validated['employee_id']['separator'],
            ]
        );

        return redirect()->route('tenant.settings.index', [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
        ])->with('success', 'Settings updated successfully.');
    }
}
