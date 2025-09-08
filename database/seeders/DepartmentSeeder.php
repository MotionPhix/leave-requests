<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
  public function run(): void
  {
    // Get the workspace created in WorkspaceSeeder
    $workspace = Workspace::first();
    
    // Create main departments
    $departments = [
      [
        'name' => 'Executive Office',
        'code' => 'EXE',
        'description' => 'Executive management and leadership',
        'workspace_id' => $workspace->id,
      ],
      [
        'name' => 'Human Resources',
        'code' => 'HR',
        'description' => 'Personnel management and development',
        'workspace_id' => $workspace->id,
      ],
      [
        'name' => 'Information Technology',
        'code' => 'IT',
        'description' => 'Technology and systems management',
        'workspace_id' => $workspace->id,
      ],
      [
        'name' => 'Finance',
        'code' => 'FIN',
        'description' => 'Financial management and accounting',
        'workspace_id' => $workspace->id,
      ],
      [
        'name' => 'Operations',
        'code' => 'OPS',
        'description' => 'Business operations and logistics',
        'workspace_id' => $workspace->id,
      ],
    ];

    foreach ($departments as $dept) {
      Department::create($dept);
    }
  }
}
