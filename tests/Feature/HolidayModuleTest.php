<?php

use App\Models\Holiday;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create();
    $this->user->workspaces()->attach($this->workspace, ['role' => 'owner']);
});

describe('Holiday Model', function () {
    it('can create a holiday', function () {
        $holiday = Holiday::factory()
            ->for($this->workspace)
            ->create([
                'name' => 'Test Holiday',
                'start_date' => '2025-12-25',
                'end_date' => '2025-12-25',
            ]);

        expect($holiday->name)->toBe('Test Holiday');
        expect($holiday->workspace_id)->toBe($this->workspace->id);
        expect($holiday->start_date->toDateString())->toBe('2025-12-25');
    });

    it('can determine if holiday is multi-day', function () {
        $singleDay = Holiday::factory()
            ->for($this->workspace)
            ->singleDay()
            ->create();

        $multiDay = Holiday::factory()
            ->for($this->workspace)
            ->multiDay(3)
            ->create();

        expect($singleDay->isMultiDay())->toBeFalse();
        expect($multiDay->isMultiDay())->toBeTrue();
    });

    it('can calculate duration in days', function () {
        $singleDay = Holiday::factory()
            ->for($this->workspace)
            ->singleDay()
            ->create();

        $threeDays = Holiday::factory()
            ->for($this->workspace)
            ->multiDay(3)
            ->create();

        expect($singleDay->getDurationInDays())->toBe(1);
        expect($threeDays->getDurationInDays())->toBe(4); // 3 days difference + 1
    });

    it('can filter holidays by date range', function () {
        // Create holidays in different months
        $januaryHoliday = Holiday::factory()
            ->for($this->workspace)
            ->create(['start_date' => '2025-01-01', 'end_date' => '2025-01-01']);

        $marchHoliday = Holiday::factory()
            ->for($this->workspace)
            ->create(['start_date' => '2025-03-15', 'end_date' => '2025-03-15']);

        $decemberHoliday = Holiday::factory()
            ->for($this->workspace)
            ->create(['start_date' => '2025-12-25', 'end_date' => '2025-12-25']);

        // Test date range filtering
        $q1Holidays = Holiday::inDateRange('2025-01-01', '2025-03-31')->get();
        $q4Holidays = Holiday::inDateRange('2025-10-01', '2025-12-31')->get();

        expect($q1Holidays)->toHaveCount(2);
        expect($q4Holidays)->toHaveCount(1);
        expect($q1Holidays->pluck('id'))->toContain($januaryHoliday->id, $marchHoliday->id);
        expect($q4Holidays->first()->id)->toBe($decemberHoliday->id);
    });

    it('can filter holidays by role visibility', function () {
        $visibleHoliday = Holiday::factory()
            ->for($this->workspace)
            ->visibleToEmployees()
            ->create();

        $hiddenHoliday = Holiday::factory()
            ->for($this->workspace)
            ->hiddenFromEmployees()
            ->create();

        $employeeHolidays = Holiday::visibleToRole('employee')->get();
        $ownerHolidays = Holiday::visibleToRole('owner')->get();

        expect($employeeHolidays)->toHaveCount(1);
        expect($employeeHolidays->first()->id)->toBe($visibleHoliday->id);
        expect($ownerHolidays)->toHaveCount(2); // Owners see all holidays
    });

    it('can format date range for display', function () {
        $singleDay = Holiday::factory()
            ->for($this->workspace)
            ->create([
                'start_date' => '2025-07-04',
                'end_date' => '2025-07-04',
            ]);

        $multiDay = Holiday::factory()
            ->for($this->workspace)
            ->create([
                'start_date' => '2025-11-27',
                'end_date' => '2025-11-29',
            ]);

        expect($singleDay->date_range)->toBe('Jul 4, 2025');
        expect($multiDay->date_range)->toBe('Nov 27 - Nov 29, 2025');
    });
});

describe('Holiday Factory', function () {
    it('can create holidays with different types', function () {
        $national = Holiday::factory()
            ->for($this->workspace)
            ->nationalHoliday()
            ->create();

        $company = Holiday::factory()
            ->for($this->workspace)
            ->type('Company Holiday')
            ->create();

        expect($national->type)->toBe('National Holiday');
        expect($national->color)->toBe('#dc2626');
        expect($national->is_recurring)->toBeTrue();

        expect($company->type)->toBe('Company Holiday');
        expect($company->color)->toBe('#2563eb');
    });

    it('can create recurring and one-time holidays', function () {
        $recurring = Holiday::factory()
            ->for($this->workspace)
            ->recurring()
            ->create();

        $oneTime = Holiday::factory()
            ->for($this->workspace)
            ->oneTime()
            ->create();

        expect($recurring->is_recurring)->toBeTrue();
        expect($recurring->recurrence_pattern)->toBe('yearly');

        expect($oneTime->is_recurring)->toBeFalse();
        expect($oneTime->recurrence_pattern)->toBeNull();
    });
});

describe('Holiday API', function () {
    beforeEach(function () {
        $this->actingAs($this->user, 'sanctum');
    });

    it('can get holidays via API', function () {
        Holiday::factory()
            ->for($this->workspace)
            ->count(3)
            ->create();

        $response = $this->getJson('/api/holidays?workspace_id=' . $this->workspace->id);

        $response->assertOk()
            ->assertJsonStructure([
                'holidays' => [
                    '*' => [
                        'id',
                        'title',
                        'start',
                        'end',
                        'color',
                        'extendedProps' => [
                            'type',
                            'holidayType',
                            'description',
                            'isRecurring',
                            'duration',
                        ]
                    ]
                ],
                'total',
                'meta'
            ]);

        expect($response->json('total'))->toBe(3);
    });

    it('can get holiday statistics', function () {
        Holiday::factory()
            ->for($this->workspace)
            ->nationalHoliday()
            ->count(2)
            ->create();

        Holiday::factory()
            ->for($this->workspace)
            ->type('Company Holiday')
            ->create();

        $response = $this->getJson('/api/holidays/stats?workspace_id=' . $this->workspace->id);

        $response->assertOk()
            ->assertJsonStructure([
                'year',
                'workspace_id',
                'statistics' => [
                    'total_holidays',
                    'by_type',
                    'recurring_holidays',
                    'multi_day_holidays',
                    'total_holiday_days',
                    'upcoming_holidays',
                    'month_breakdown'
                ]
            ]);

        $stats = $response->json('statistics');
        expect($stats['total_holidays'])->toBe(3);
        expect($stats['by_type']['National Holiday'])->toBe(2);
        expect($stats['by_type']['Company Holiday'])->toBe(1);
    });

    it('can check holiday conflicts', function () {
        // Create a holiday on July 4th
        Holiday::factory()
            ->for($this->workspace)
            ->create([
                'start_date' => '2025-07-04',
                'end_date' => '2025-07-04',
            ]);

        // Check for conflicts with overlapping date
        $response = $this->getJson('/api/holidays/conflicts?' . http_build_query([
            'workspace_id' => $this->workspace->id,
            'start_date' => '2025-07-04',
            'end_date' => '2025-07-04',
        ]));

        $response->assertOk()
            ->assertJson([
                'has_conflicts' => true,
                'conflict_count' => 1,
            ]);

        // Check for no conflicts with different date
        $response = $this->getJson('/api/holidays/conflicts?' . http_build_query([
            'workspace_id' => $this->workspace->id,
            'start_date' => '2025-07-05',
            'end_date' => '2025-07-05',
        ]));

        $response->assertOk()
            ->assertJson([
                'has_conflicts' => false,
                'conflict_count' => 0,
            ]);
    });

    it('can export holidays in different formats', function () {
        Holiday::factory()
            ->for($this->workspace)
            ->count(2)
            ->create();

        // Test CSV export
        $csvResponse = $this->getJson('/api/holidays/export/csv?workspace_id=' . $this->workspace->id);
        $csvResponse->assertOk()
            ->assertHeader('content-type', 'text/csv');

        // Test JSON export  
        $jsonResponse = $this->getJson('/api/holidays/export/json?workspace_id=' . $this->workspace->id);
        $jsonResponse->assertOk()
            ->assertJsonStructure([
                'workspace',
                'year',
                'exported_at',
                'total_holidays',
                'holidays'
            ]);

        // Test ICS export
        $icsResponse = $this->getJson('/api/holidays/export/ics?workspace_id=' . $this->workspace->id);
        $icsResponse->assertOk()
            ->assertHeader('content-type', 'text/calendar; charset=utf-8');
    });

    it('rejects invalid export formats', function () {
        $response = $this->getJson('/api/holidays/export/invalid?workspace_id=' . $this->workspace->id);
        
        $response->assertStatus(400)
            ->assertJson(['error' => 'Unsupported format. Use: ics, csv, json']);
    });
});

describe('Holiday Command', function () {
    it('can generate recurring holidays', function () {
        // Create a recurring holiday for 2025
        $baseHoliday = Holiday::factory()
            ->for($this->workspace)
            ->recurring()
            ->create([
                'start_date' => '2025-01-01',
                'end_date' => '2025-01-01',
                'name' => 'New Year Test'
            ]);

        // Generate for 2026
        $this->artisan('holidays:generate', ['year' => 2026, '--years' => 1])
            ->assertExitCode(0);

        // Verify the holiday was generated for 2026
        $generated = Holiday::where('workspace_id', $this->workspace->id)
            ->where('name', 'New Year Test')
            ->whereYear('start_date', 2026)
            ->first();

        expect($generated)->not->toBeNull();
        expect($generated->start_date->toDateString())->toBe('2026-01-01');
        expect($generated->is_recurring)->toBeFalse(); // Generated instances are not recurring
    });

    it('skips existing holidays when generating', function () {
        // Create a recurring holiday
        $baseHoliday = Holiday::factory()
            ->for($this->workspace)
            ->recurring()
            ->create([
                'start_date' => '2025-07-04',
                'end_date' => '2025-07-04',
                'name' => 'Independence Day'
            ]);

        // Manually create the 2026 version
        Holiday::factory()
            ->for($this->workspace)
            ->create([
                'start_date' => '2026-07-04',
                'end_date' => '2026-07-04',
                'name' => 'Independence Day'
            ]);

        $initialCount = Holiday::where('workspace_id', $this->workspace->id)->count();

        // Try to generate for 2026 (should skip existing)
        $this->artisan('holidays:generate', ['year' => 2026, '--years' => 1]);

        $finalCount = Holiday::where('workspace_id', $this->workspace->id)->count();

        expect($finalCount)->toBe($initialCount); // Should be the same
    });
});