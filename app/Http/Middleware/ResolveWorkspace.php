<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class ResolveWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        // Handle new route parameter format: {tenant} = "slug:uuid"
        $tenant = $request->route('tenant');

        if ($tenant) {
            // Parse the "slug:uuid" format
            $parts = explode(':', $tenant);
            if (count($parts) !== 2) {
                abort(404, 'Invalid tenant format');
            }

            [$slug, $uuid] = $parts;
        } else {
            // Fallback to old format for backward compatibility
            $slug = $request->route('tenant_slug') ?? $request->route('workspace');
            $uuid = $request->route('tenant_uuid') ?? $request->route('workspace_uuid');
        }

        if ($slug && $uuid) {
            $workspace = Workspace::query()
                ->where('slug', $slug)
                ->where('uuid', $uuid)
                ->first();

            if (! $workspace) {
                abort(404);
            }

            // Optional membership enforcement
            if ($request->user()) {
                $isOwner = $workspace->owner_id == $request->user()->id;
                $isMember = $workspace->users()->where('user_id', $request->user()->id)->exists();
                
                // Owners should always have access, regardless of pivot table
                if (!$isOwner && !$isMember) {
                    // Debug logging
                    logger('ResolveWorkspace 403 Debug', [
                        'user_id' => $request->user()->id,
                        'user_name' => $request->user()->name,
                        'workspace_id' => $workspace->id,
                        'workspace_name' => $workspace->name,
                        'workspace_owner_id' => $workspace->owner_id,
                        'is_member' => $isMember,
                        'is_owner' => $isOwner,
                        'url' => $request->fullUrl(),
                    ]);
                    
                    abort(403, "Access denied. User ID {$request->user()->id} ({$request->user()->name}) is not a member of workspace '{$workspace->name}' (ID: {$workspace->id}). Owner: {$isOwner}, Member: {$isMember}");
                }
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
