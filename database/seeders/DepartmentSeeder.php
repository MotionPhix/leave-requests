<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
  public function run(): void
  {
    // Create main departments
    $departments = [
      [
        'name' => 'Executive Office',
        'code' => 'EXE',
        'description' => 'Executive management and leadership',
      ],
      [
        'name' => 'Human Resources',
        'code' => 'HR',
        'description' => 'Personnel management and development',
      ],
      [
        'name' => 'Information Technology',
        'code' => 'IT',
        'description' => 'Technology and systems management',
      ],
      [
        'name' => 'Finance',
        'code' => 'FIN',
        'description' => 'Financial management and accounting',
      ],
      [
        'name' => 'Operations',
        'code' => 'OPS',
        'description' => 'Business operations and logistics',
      ],
    ];

    foreach ($departments as $dept) {
      Department::create($dept);
    }
  }
}
