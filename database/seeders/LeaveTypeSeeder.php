<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $leaveTypes = [
      ['name' => 'Annual Leave', 'description' => 'Paid time off for vacation or personal use', 'max_days_per_year' => 21],
      ['name' => 'Sick Leave', 'description' => 'Time off due to illness', 'max_days_per_year' => 10],
      ['name' => 'Maternity Leave', 'description' => 'Leave for maternity reasons', 'max_days_per_year' => 90],
    ];

    foreach ($leaveTypes as $type) {
      LeaveType::create($type);
    }
  }
}
