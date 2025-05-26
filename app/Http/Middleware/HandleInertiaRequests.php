<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
  /**
   * The root template that's loaded on the first page visit.
   *
   * @see https://inertiajs.com/server-side-setup#root-template
   *
   * @var string
   */
  protected $rootView = 'app';

  /**
   * Determines the current asset version.
   *
   * @see https://inertiajs.com/asset-versioning
   */
  public function version(Request $request): ?string
  {
    return parent::version($request);
  }

  /**
   * Define the props that are shared by default.
   *
   * @see https://inertiajs.com/shared-data
   *
   * @return array<string, mixed>
   */
  public function share(Request $request): array
  {
    [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

    return [
      ...parent::share($request),
      'name' => config('app.name'),
      'quote' => ['message' => trim($message), 'author' => trim($author)],
      'auth' => [
        'user' => function () use ($request) {
          $user = $request->user()
            ? $request->user()->load('roles', 'permissions')
            : null;

          // Precompute "can" based on permissions
          $permissions = [
            'create leave', 'approve leave', 'reject leave', 'view leave',
            'create user', 'edit user', 'delete user', 'revoke user', 'view user',
            'view reports',
          ];

          $can = [];

          foreach ($permissions as $permission) {
            $can[$permission] = $user?->can($permission);
          }

          return [
            'id' => $user?->id,
            'name' => $user?->name,
            'email' => $user?->email,
            'isEmployee' => $user?->hasRole('Employee'),
            'can' => $can,
          ];
        },
      ],
      'flash' => [
        'success' => fn () => $request->session()->get('success'),
        'error' => fn () => $request->session()->get('error'),
      ],
      'ziggy' => [
        ...(new Ziggy)->toArray(),
        'location' => $request->url(),
      ],
      'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
    ];
  }
}
