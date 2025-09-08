<?php

namespace Database\Seeders;

use App\Models\EmployeeIdSetting;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class EmployeeIdSettingsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Get the workspace created in WorkspaceSeeder
    $workspace = Workspace::first();
    
    EmployeeIdSetting::create([
      'prefix' => 'EMP',
      'separator' => '-',
      'number_length' => 4,
      'suffix' => null,
      'include_year' => false,
      'year_format' => 'y',
      'workspace_id' => $workspace->id,
    ]);
  }
}
