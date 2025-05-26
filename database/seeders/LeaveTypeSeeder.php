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
      ['name' => 'Annual Leave', 'description' => 'Paid time off for vacation or personal use', 'max_days_per_year' => 21,'color'=>'#10b981'],
      ['name' => 'Sick Leave', 'description' => 'Time off due to illness', 'max_days_per_year' => 10,'color'=>'#f43f5e'],
      ['name' => 'Maternity Leave', 'description' => 'Leave for maternity reasons', 'max_days_per_year' => 90,'color'=>'#8b5cf6'],
    ];

    foreach ($leaveTypes as $type) {
      LeaveType::create($type);
    }
  }
}
