<?php

namespace Database\Seeders;

use App\Models\EmployeeIdSetting;
use Illuminate\Database\Seeder;

class EmployeeIdSettingsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    EmployeeIdSetting::create([
      'prefix' => 'EMP',
      'separator' => '-',
      'number_length' => 4,
      'suffix' => null,
      'include_year' => false,
      'year_format' => 'y'
    ]);
  }
}
