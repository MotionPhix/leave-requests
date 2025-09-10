<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class HolidaySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Get the workspace created in UserSeeder
    $workspace = Workspace::first();
    
    $holidays = [
      [
        'name' => 'New Year\'s Day',
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-01',
        'date' => '2025-01-01',
        'description' => 'The first day of the calendar year.',
        'type' => 'National Holiday',
        'color' => '#dc2626',
        'is_recurring' => true,
        'recurrence_pattern' => 'yearly',
        'is_visible_to_employees' => true,
      ],
      [
        'name' => 'John Chilembwe Day',
        'start_date' => '2025-01-15',
        'end_date' => '2025-01-15',
        'date' => '2025-01-15',
        'description' => 'Commemorates John Chilembwe, a Baptist minister who led an uprising against colonial rule.',
        'type' => 'National Holiday',
        'color' => '#dc2626',
        'is_recurring' => true,
        'recurrence_pattern' => 'yearly',
        'is_visible_to_employees' => true,
      ],
      [
        'name' => 'Martyrs\' Day',
        'start_date' => '2025-03-03',
        'end_date' => '2025-03-03',
        'date' => '2025-03-03',
        'description' => 'Commemorates those who died in the struggle for independence.',
        'type' => 'National Holiday',
        'color' => '#dc2626',
        'is_recurring' => true,
        'recurrence_pattern' => 'yearly',
        'is_visible_to_employees' => true,
      ],
      [
        'name' => 'Good Friday',
        'start_date' => '2025-04-18',
        'end_date' => '2025-04-18',
        'date' => '2025-04-18',
        'description' => 'Christian holiday commemorating the crucifixion of Jesus.',
        'type' => 'Religious Holiday',
        'color' => '#7c3aed',
        'is_recurring' => true,
        'recurrence_pattern' => 'yearly',
        'is_visible_to_employees' => true,
      ],
      [
        'name' => 'Easter Monday',
        'start_date' => '2025-04-21',
        'end_date' => '2025-04-21',
        'date' => '2025-04-21',
        'description' => 'Christian holiday celebrating the resurrection of Jesus.',
        'type' => 'Religious Holiday',
        'color' => '#7c3aed',
        'is_recurring' => true,
        'recurrence_pattern' => 'yearly',
        'is_visible_to_employees' => true,
      ],
      [
        'name' => 'Labour Day',
        'start_date' => '2025-05-01',
        'end_date' => '2025-05-01',
        'date' => '2025-05-01',
        'description' => 'International Workers\' Day celebrating the achievements of workers.',
        'type' => 'National Holiday',
        'color' => '#dc2626',
        'is_recurring' => true,
        'recurrence_pattern' => 'yearly',
        'is_visible_to_employees' => true,
      ],
      [
        'name' => 'Kamuzu Day',
        'start_date' => '2025-05-14',
        'end_date' => '2025-05-14',
        'date' => '2025-05-14',
        'description' => 'Commemorates Malawi\'s first president, Dr. Hastings Kamuzu Banda.',
        'type' => 'National Holiday',
        'color' => '#dc2626',
        'is_recurring' => true,
        'recurrence_pattern' => 'yearly',
        'is_visible_to_employees' => true,
      ],
      [
        'name' => 'Independence Day',
        'start_date' => '2025-07-06',
        'end_date' => '2025-07-06',
        'date' => '2025-07-06',
        'description' => 'Celebrates Malawi\'s independence from British rule in 1964.',
        'type' => 'National Holiday',
        'color' => '#dc2626',
        'is_recurring' => true,
        'recurrence_pattern' => 'yearly',
        'is_visible_to_employees' => true,
      ],
      [
        'name' => 'Mothers\' Day',
        'start_date' => '2025-10-15',
        'end_date' => '2025-10-15',
        'date' => '2025-10-15',
        'description' => 'Honours mothers and maternal bonds.',
        'type' => 'National Holiday',
        'color' => '#dc2626',
        'is_recurring' => true,
        'recurrence_pattern' => 'yearly',
        'is_visible_to_employees' => true,
      ],
      [
        'name' => 'Christmas Day',
        'start_date' => '2025-12-25',
        'end_date' => '2025-12-25',
        'date' => '2025-12-25',
        'description' => 'Christian holiday celebrating the birth of Jesus Christ.',
        'type' => 'Religious Holiday',
        'color' => '#7c3aed',
        'is_recurring' => true,
        'recurrence_pattern' => 'yearly',
        'is_visible_to_employees' => true,
      ],
      [
        'name' => 'Boxing Day',
        'start_date' => '2025-12-26',
        'end_date' => '2025-12-26',
        'date' => '2025-12-26',
        'description' => 'Public holiday following Christmas Day.',
        'type' => 'National Holiday',
        'color' => '#dc2626',
        'is_recurring' => true,
        'recurrence_pattern' => 'yearly',
        'is_visible_to_employees' => true,
      ],
    ];

    foreach ($holidays as $holiday) {
      $holiday['workspace_id'] = $workspace->id;
      Holiday::create($holiday);
    }
  }
}
