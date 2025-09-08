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
        'date' => '2025-01-01',
        'description' => 'The first day of the calendar year.',
        'type' => 'Public Holiday',
        'is_recurring' => true,
      ],
      [
        'name' => 'John Chilembwe Day',
        'date' => '2025-01-15',
        'description' => 'Commemorates John Chilembwe, a Baptist minister who led an uprising against colonial rule.',
        'type' => 'Public Holiday',
        'is_recurring' => true,
      ],
      [
        'name' => 'Martyrs\' Day',
        'date' => '2025-03-03',
        'description' => 'Commemorates those who died in the struggle for independence.',
        'type' => 'Public Holiday',
        'is_recurring' => true,
      ],
      [
        'name' => 'Good Friday',
        'date' => '2025-04-18',
        'description' => 'Christian holiday commemorating the crucifixion of Jesus.',
        'type' => 'Public Holiday',
        'is_recurring' => true,
      ],
      [
        'name' => 'Easter Monday',
        'date' => '2025-04-21',
        'description' => 'Christian holiday celebrating the resurrection of Jesus.',
        'type' => 'Public Holiday',
        'is_recurring' => true,
      ],
      [
        'name' => 'Labour Day',
        'date' => '2025-05-01',
        'description' => 'International Workers\' Day celebrating the achievements of workers.',
        'type' => 'Public Holiday',
        'is_recurring' => true,
      ],
      [
        'name' => 'Kamuzu Day',
        'date' => '2025-05-14',
        'description' => 'Commemorates Malawi\'s first president, Dr. Hastings Kamuzu Banda.',
        'type' => 'Public Holiday',
        'is_recurring' => true,
      ],
      [
        'name' => 'Independence Day',
        'date' => '2025-07-06',
        'description' => 'Celebrates Malawi\'s independence from British rule in 1964.',
        'type' => 'Public Holiday',
        'is_recurring' => true,
      ],
      [
        'name' => 'Mothers\' Day',
        'date' => '2025-10-15',
        'description' => 'Honours mothers and maternal bonds.',
        'type' => 'Public Holiday',
        'is_recurring' => true,
      ],
      [
        'name' => 'Christmas Day',
        'date' => '2025-12-25',
        'description' => 'Christian holiday celebrating the birth of Jesus Christ.',
        'type' => 'Public Holiday',
        'is_recurring' => true,
      ],
      [
        'name' => 'Boxing Day',
        'date' => '2025-12-26',
        'description' => 'Public holiday following Christmas Day.',
        'type' => 'Public Holiday',
        'is_recurring' => true,
      ],
    ];

    foreach ($holidays as $holiday) {
      $holiday['workspace_id'] = $workspace->id;
      Holiday::create($holiday);
    }
  }
}
