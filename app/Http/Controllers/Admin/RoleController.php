<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
  public function index()
  {
    $roles = Role::with('permissions')->get();
    $permissions = Permission::all();

    return Inertia::render('admin/roles/Index', [
      'roles' => $roles,
      'permissions' => $permissions,
    ]);
  }

  public function create(): \Inertia\Response
  {
    $permissions = Permission::all(['id', 'name']);

    return Inertia::render('admin/roles/Create', [
      'permissions' => $permissions,
    ]);
  }

  public function store(Request $request)
  {
    $normalizedName = strtolower(trim($request->name));

    $request->validate([
      'name' => [
        'required',
        'string',
        'max:255',
        function ($attribute, $value, $fail) use ($normalizedName) {
          if (Role::query()
            ->whereRaw('LOWER(name) = ?', [$normalizedName])
            ->exists()) {
            $fail('This role already exists.');
          }
        },
        'regex:/^[a-zA-Z\s]+$/' // Only letters and spaces allowed
      ],
      'permissions' => ['required', 'array', 'min:1'],
      'permissions.*' => ['exists:permissions,id'],
    ], [
      'name.required' => 'Provide a role name.',
      'name.regex' => 'Role name can only contain letters and spaces.',
      'permissions.required' => 'At least one permission must be selected.',
      'permissions.array' => 'Permissions must be selected from the list.',
      'permissions.min' => 'Please select at least one permission.',
      'permissions.*.exists' => 'One or more selected permissions are invalid.',
    ]);

    // Convert first letter of each word to uppercase
    $formattedName = ucwords(strtolower($request->name));

    $role = Role::create(['name' => $formattedName]);
    $role->syncPermissions($request->permissions);

    return back()->with('success', 'Role created successfully.');
  }

  public function update(Request $request, Role $role)
  {
    $normalizedName = strtolower(trim($request->name));

    $request->validate([
      'name' => [
        'required',
        'string',
        'max:255',
        function ($attribute, $value, $fail) use ($normalizedName, $role) {
          if (Role::query()
            ->where('id', '!=', $role->id)
            ->whereRaw('LOWER(name) = ?', [$normalizedName])
            ->exists()) {
            $fail('This role already exists.');
          }
        },
        'regex:/^[a-zA-Z\s]+$/' // Only letters and spaces allowed
      ],
      'permissions' => ['required', 'array', 'min:1'],
      'permissions.*' => ['exists:permissions,id'],
    ], [
      'name.required' => 'Provide a role name.',
      'name.regex' => 'Role name can only contain letters and spaces.',
      'permissions.required' => 'At least one permission must be selected.',
      'permissions.array' => 'Permissions must be selected from the list.',
      'permissions.min' => 'Please select at least one permission.',
      'permissions.*.exists' => 'One or more selected permissions are invalid.',
    ]);

    // Prevent updating Admin role name
    if ($role->name === 'Admin' && $normalizedName !== 'admin') {
      return back()->with('error', 'Cannot modify the Admin role name.');
    }

    // Convert first letter of each word to uppercase
    $formattedName = ucwords(strtolower($request->name));

    $role->update(['name' => $formattedName]);
    $role->syncPermissions($request->permissions);

    return back()->with('success', 'Role updated successfully.');
  }

  public function destroy(Role $role)
  {
    if ($role->name === 'Admin') {
      return back()->with('error', 'Cannot delete Admin role.');
    }

    $role->delete();
    return back()->with('success', 'Role deleted successfully.');
  }
}
