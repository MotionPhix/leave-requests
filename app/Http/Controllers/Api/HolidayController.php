<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class HolidayController extends Controller
{
    /**
     * Get holidays for calendar display
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
            'role' => 'nullable|string|in:employee,manager,hr,owner',
            'type' => 'nullable|string',
        ]);

        $workspace = Workspace::findOrFail($request->workspace_id);
        $userRole = $request->get('role', 'employee');
        
        $start = $request->get('start', now()->startOfYear());
        $end = $request->get('end', now()->endOfYear());

        $query = Holiday::where('workspace_id', $workspace->id)
            ->visibleToRole($userRole)
            ->inDateRange($start, $end);

        // Filter by type if provided
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $holidays = $query->orderBy('start_date')
            ->get()
            ->map(function ($holiday) use ($userRole) {
                return [
                    'id' => $holiday->uuid,
                    'title' => $holiday->name,
                    'start' => $holiday->start_date?->toDateString() ?? $holiday->date?->toDateString(),
                    'end' => $holiday->end_date?->addDay()->toDateString() ?? $holiday->date?->addDay()->toDateString(),
                    'color' => $holiday->color ?? '#ef4444',
                    'textColor' => '#ffffff',
                    'allDay' => true,
                    'display' => 'block',
                    'extendedProps' => [
                        'type' => 'holiday',
                        'holidayType' => $holiday->type,
                        'description' => $holiday->description,
                        'isRecurring' => $holiday->is_recurring,
                        'recurrencePattern' => $holiday->recurrence_pattern,
                        'duration' => $holiday->getDurationInDays(),
                        'isMultiDay' => $holiday->isMultiDay(),
                        'canManage' => $userRole === 'owner',
                        'visibleToEmployees' => $holiday->is_visible_to_employees,
                    ],
                ];
            });

        return response()->json([
            'holidays' => $holidays,
            'total' => $holidays->count(),
            'meta' => [
                'start_date' => $start,
                'end_date' => $end,
                'workspace_id' => $workspace->id,
                'user_role' => $userRole,
            ],
        ]);
    }

    /**
     * Get holiday types and statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'year' => 'nullable|integer|min:2020|max:2050',
        ]);

        $workspace = Workspace::findOrFail($request->workspace_id);
        $year = $request->get('year', now()->year);

        $query = Holiday::where('workspace_id', $workspace->id)
            ->whereYear('start_date', $year);

        $holidays = $query->get();

        $stats = [
            'total_holidays' => $holidays->count(),
            'by_type' => $holidays->groupBy('type')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'recurring_holidays' => $holidays->where('is_recurring', true)->count(),
            'multi_day_holidays' => $holidays->filter(fn($h) => $h->isMultiDay())->count(),
            'total_holiday_days' => $holidays->sum(fn($h) => $h->getDurationInDays()),
            'upcoming_holidays' => $holidays
                ->filter(fn($h) => $h->start_date >= now())
                ->count(),
            'month_breakdown' => $holidays->groupBy(fn($h) => $h->start_date->format('F'))
                ->map(fn($group) => $group->count())
                ->toArray(),
        ];

        return response()->json([
            'year' => $year,
            'workspace_id' => $workspace->id,
            'statistics' => $stats,
        ]);
    }

    /**
     * Check for holiday conflicts
     */
    public function conflicts(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'exclude_id' => 'nullable|string|exists:holidays,uuid',
        ]);

        $workspace = Workspace::findOrFail($request->workspace_id);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $conflictsQuery = Holiday::where('workspace_id', $workspace->id)
            ->inDateRange($startDate, $endDate);

        // Exclude specific holiday if editing
        if ($request->filled('exclude_id')) {
            $conflictsQuery->where('uuid', '!=', $request->exclude_id);
        }

        $conflicts = $conflictsQuery->get()->map(function ($holiday) {
            return [
                'id' => $holiday->uuid,
                'name' => $holiday->name,
                'type' => $holiday->type,
                'start_date' => $holiday->start_date,
                'end_date' => $holiday->end_date,
                'date_range' => $holiday->date_range,
                'is_recurring' => $holiday->is_recurring,
            ];
        });

        return response()->json([
            'conflicts' => $conflicts,
            'has_conflicts' => $conflicts->count() > 0,
            'conflict_count' => $conflicts->count(),
            'message' => $conflicts->count() > 0 
                ? "Found {$conflicts->count()} conflicting holiday(s)" 
                : 'No conflicts found',
        ]);
    }

    /**
     * Export holidays in various formats
     */
    public function export(Request $request, string $format)
    {
        $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'year' => 'nullable|integer',
            'type' => 'nullable|string',
        ]);

        $workspace = Workspace::findOrFail($request->workspace_id);
        $year = $request->get('year', now()->year);

        $query = Holiday::where('workspace_id', $workspace->id)
            ->whereYear('start_date', $year);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $holidays = $query->orderBy('start_date')->get();

        switch (strtolower($format)) {
            case 'ics':
                return $this->exportToICS($holidays, $workspace, $year);
            case 'csv':
                return $this->exportToCSV($holidays, $workspace, $year);
            case 'json':
                return $this->exportToJSON($holidays, $workspace, $year);
            default:
                return response()->json(['error' => 'Unsupported format. Use: ics, csv, json'], 400);
        }
    }

    /**
     * Export holidays to ICS (iCalendar) format
     */
    private function exportToICS($holidays, $workspace, $year)
    {
        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//{$workspace->name}//Holiday Calendar//EN\r\n";
        $icsContent .= "CALSCALE:GREGORIAN\r\n";
        $icsContent .= "METHOD:PUBLISH\r\n";
        $icsContent .= "X-WR-CALNAME:{$workspace->name} Holidays {$year}\r\n";
        $icsContent .= "X-WR-CALDESC:Company holidays for {$workspace->name}\r\n";

        foreach ($holidays as $holiday) {
            $icsContent .= "BEGIN:VEVENT\r\n";
            $icsContent .= "UID:holiday-{$holiday->uuid}@{$workspace->slug}.holidays\r\n";
            $icsContent .= "DTSTART;VALUE=DATE:" . $holiday->start_date->format('Ymd') . "\r\n";
            $icsContent .= "DTEND;VALUE=DATE:" . $holiday->end_date->addDay()->format('Ymd') . "\r\n";
            $icsContent .= "SUMMARY:{$holiday->name}\r\n";
            
            if ($holiday->description) {
                $icsContent .= "DESCRIPTION:" . str_replace(["\n", "\r"], "\\n", $holiday->description) . "\r\n";
            }
            
            $icsContent .= "CATEGORIES:{$holiday->type}\r\n";
            $icsContent .= "STATUS:CONFIRMED\r\n";
            $icsContent .= "TRANSP:TRANSPARENT\r\n";
            $icsContent .= "CREATED:" . $holiday->created_at->format('Ymd\THis\Z') . "\r\n";
            $icsContent .= "LAST-MODIFIED:" . $holiday->updated_at->format('Ymd\THis\Z') . "\r\n";
            
            if ($holiday->is_recurring) {
                $icsContent .= "RRULE:FREQ=YEARLY\r\n";
            }
            
            $icsContent .= "END:VEVENT\r\n";
        }

        $icsContent .= "END:VCALENDAR\r\n";

        return response($icsContent)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$workspace->slug}-holidays-{$year}.ics\"");
    }

    /**
     * Export holidays to CSV format
     */
    private function exportToCSV($holidays, $workspace, $year)
    {
        $csvData = [];
        $csvData[] = [
            'Holiday Name',
            'Start Date',
            'End Date',
            'Duration (Days)',
            'Type',
            'Description',
            'Recurring',
            'Recurrence Pattern',
            'Visible to Employees',
            'Color',
            'Created Date'
        ];

        foreach ($holidays as $holiday) {
            $csvData[] = [
                $holiday->name,
                $holiday->start_date->format('Y-m-d'),
                $holiday->end_date->format('Y-m-d'),
                $holiday->getDurationInDays(),
                $holiday->type,
                $holiday->description ?: 'N/A',
                $holiday->is_recurring ? 'Yes' : 'No',
                $holiday->recurrence_pattern ?: 'N/A',
                $holiday->is_visible_to_employees ? 'Yes' : 'No',
                $holiday->color,
                $holiday->created_at->format('Y-m-d H:i:s')
            ];
        }

        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$workspace->slug}-holidays-{$year}.csv\"");
    }

    /**
     * Export holidays to JSON format
     */
    private function exportToJSON($holidays, $workspace, $year)
    {
        $data = [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            'year' => $year,
            'exported_at' => now()->toISOString(),
            'total_holidays' => $holidays->count(),
            'holidays' => $holidays->map(function ($holiday) {
                return [
                    'id' => $holiday->uuid,
                    'name' => $holiday->name,
                    'start_date' => $holiday->start_date->format('Y-m-d'),
                    'end_date' => $holiday->end_date->format('Y-m-d'),
                    'duration_days' => $holiday->getDurationInDays(),
                    'type' => $holiday->type,
                    'description' => $holiday->description,
                    'color' => $holiday->color,
                    'is_recurring' => $holiday->is_recurring,
                    'recurrence_pattern' => $holiday->recurrence_pattern,
                    'is_visible_to_employees' => $holiday->is_visible_to_employees,
                    'is_multi_day' => $holiday->isMultiDay(),
                    'created_at' => $holiday->created_at->toISOString(),
                    'updated_at' => $holiday->updated_at->toISOString(),
                ];
            }),
        ];

        return response()->json($data)
            ->header('Content-Disposition', "attachment; filename=\"{$workspace->slug}-holidays-{$year}.json\"");
    }
}