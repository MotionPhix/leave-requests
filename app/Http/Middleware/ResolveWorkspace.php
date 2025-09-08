<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Permission\PermissionRegistrar;

class ResolveWorkspace
{
  public function handle(Request $request, Closure $next): Response
  {
  $slug = $request->route('tenant_slug') ?? $request->route('workspace');
  $uuid = $request->route('tenant_uuid') ?? $request->route('workspace_uuid');

    if ($slug && $uuid) {
      $workspace = Workspace::query()
        ->where('slug', $slug)
        ->where('uuid', $uuid)
        ->first();

      if (!$workspace) {
        abort(404);
      }

      // Optional membership enforcement
      if ($request->user() && !$workspace->users()->where('user_id', $request->user()->id)->exists()) {
        abort(403);
      }

      // Bind into request for downstream usage
      $request->attributes->set('workspace', $workspace);

      // Share with Inertia
  Inertia::share('workspace', [
        'uuid' => $workspace->uuid,
        'slug' => $workspace->slug,
        'name' => $workspace->name,
      ]);

  // Configure Spatie Permission team (workspace) context
  /** @var \Spatie\Permission\PermissionRegistrar $registrar */
  $registrar = app(PermissionRegistrar::class);
  $registrar->setPermissionsTeamId($workspace->getKey());
    }

    return $next($request);
  }
}
