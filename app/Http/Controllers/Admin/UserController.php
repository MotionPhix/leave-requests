<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
  public function index()
  {
    return Inertia::render('admin/users/Index', [
      'users' => User::query()
        ->with('roles')
        ->orderBy('name')
        ->paginate(10)
        ->through(fn($user) => [
          'id' => $user->id,
          'uuid' => $user->uuid,
          'name' => $user->name,
          'email' => $user->email,
          'gender' => $user->gender,
          'role' => $user->roles->first()?->name,
          'created_at' => $user->created_at->format('M d, Y')
        ])
    ]);
  }

  public function create()
  {
    return Inertia::render('admin/users/Create', [
      'roles' => \Spatie\Permission\Models\Role::all()
        ->map(fn($role) => [
          'id' => $role->id,
          'name' => $role->name
        ])
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'email' => 'required|string|email|max:255|unique:users',
      'gender' => 'required|in:male,female',
      'password' => ['required', Password::defaults()],
      'role_id' => 'required|exists:roles,id'
    ]);

    $user = User::create([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'gender' => $validated['gender'],
      'password' => bcrypt($validated['password'])
    ]);

    $role = \Spatie\Permission\Models\Role::find($validated['role_id']);
    $user->assignRole($role);

    return redirect()->route('admin.users.index')
      ->with('success', 'User created successfully');
  }

  public function edit(User $user)
  {
    return Inertia::render('admin/users/Edit', [
      'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'gender' => $user->gender,
        'role_id' => $user->roles->first()?->id
      ],
      'roles' => \Spatie\Permission\Models\Role::all()
        ->map(fn($role) => [
          'id' => $role->id,
          'name' => $role->name
        ])
    ]);
  }

  public function update(Request $request, User $user)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
      'gender' => 'required|in:male,female',
      'password' => ['nullable', Password::defaults()],
      'role_id' => 'required|exists:roles,id'
    ]);

    $user->update([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'gender' => $validated['gender'],
      'password' => $validated['password'] ? bcrypt($validated['password']) : $user->password
    ]);

    $role = \Spatie\Permission\Models\Role::find($validated['role_id']);
    $user->syncRoles([$role]);

    return redirect()->route('admin.users.index')
      ->with('success', 'User updated successfully');
  }

  public function destroy(User $user)
  {
    $user->delete();

    return redirect()->route('admin.users.index')
      ->with('success', 'User deleted successfully');
  }
}
