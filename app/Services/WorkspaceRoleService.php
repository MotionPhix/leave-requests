<?php

namespace App\Services;

use App\Models\Workspace;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class WorkspaceRoleService
{
  public function seedCoreRoles(Workspace $workspace): void
  {
    /** @var PermissionRegistrar $registrar */
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($workspace->getKey());

    foreach (['Owner', 'Admin', 'HR', 'Manager', 'Employee'] as $role) {
      Role::firstOrCreate(['name' => $role, 'workspace_id' => $workspace->getKey()]);
    }
  }
}
