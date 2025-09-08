<?php

namespace Tests\Traits;

trait SeedsRolesAndPermissions
{
    protected function seedRolesAndPermissions(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }
}
