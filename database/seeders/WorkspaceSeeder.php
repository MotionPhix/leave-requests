<?php

namespace Database\Seeders;

use App\Models\Workspace;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the default workspace without an owner initially
        $workspace = Workspace::create([
            'name' => 'Default Company',
            'owner_id' => null, // Will be set to the admin user in UserSeeder
        ]);

        // Store the workspace ID for use in other seeders
        config(['seeding.workspace_id' => $workspace->id]);
    }
}
