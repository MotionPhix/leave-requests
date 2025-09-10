<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create a system admin (not tied to any workspace - uses special flag)
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@qhub.com',
            'position' => 'System Administrator',
            'employment_status' => 'active',
            'employment_type' => 'full-time',
            'department' => null,
            'is_system_admin' => true, // Special flag for system admin
        ]);
        // No role assignment needed - system admin uses the flag

        // Create workspace owner (scoped to a workspace)
        $owner = User::factory()->create([
            'name' => 'Workspace Owner',
            'email' => 'owner@qhub.com',
            'position' => 'Managing Director',
            'employment_status' => 'active',
            'employment_type' => 'full-time',
            'department' => null,
        ]);

        // Create the default workspace with owner as owner
        $workspace = Workspace::create([
            'name' => 'Default Company',
            'owner_id' => $owner->id,
        ]);

        // Set the current team for permission assignment
        setPermissionsTeamId($workspace->id);

        // Create workspace-scoped roles using WorkspaceRoleService
        $roleService = new \App\Services\WorkspaceRoleService;
        $roleService->seedCoreRoles($workspace);

        // Add owner to workspace and assign role
        $workspace->users()->attach($owner->id, ['role' => 'owner']);
        $owner->assignRole('Owner');

        // Create departments with workspace_id
        $this->createDepartments($workspace);

        // Create HR Manager
        $hrDepartment = Department::where('code', 'HR')->first();
        $hrManager = User::factory()->create([
            'name' => 'HR Manager',
            'email' => 'hr@qhub.com',
            'department' => $hrDepartment->id,
            'position' => 'HR Manager',
            'employment_status' => 'active',
            'employment_type' => 'full-time',
            'reporting_to' => $owner->id,
        ]);

        // Add HR manager to workspace
        $workspace->users()->attach($hrManager->id, ['role' => 'member']);
        $hrManager->assignRole('HR');
        $hrDepartment->manager_id = $hrManager->id;
        $hrDepartment->save();

        // Create department managers and employees
        Department::where('code', '!=', 'HR')->each(function ($department) use ($owner, $workspace) {
            // Create department manager
            $manager = User::factory()->create([
                'department' => $department->id,
                'position' => 'Department Manager',
                'employment_status' => 'active',
                'employment_type' => 'full-time',
                'reporting_to' => $owner->id,
            ]);

            // Add manager to workspace
            $workspace->users()->attach($manager->id, ['role' => 'member']);
            $manager->assignRole('Manager');

            // Update department with manager
            $department->manager_id = $manager->id;
            $department->save();

            // Create employees for the department
            User::factory()
                ->count(3) // Reduced count to prevent performance issues
                ->active()
                ->create([
                    'department' => $department->id,
                    'reporting_to' => $manager->id,
                    'employment_type' => 'full-time',
                ])
                ->each(function ($user) use ($workspace) {
                    // Add employee to workspace
                    $workspace->users()->attach($user->id, ['role' => 'member']);
                    $user->assignRole('Employee');
                });
        });
    }

    /**
     * Create departments with workspace ID
     */
    private function createDepartments(Workspace $workspace): void
    {
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
