<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
  public function index(Request $request)
  {
    $workspace = $request->attributes->get('workspace');

    return Inertia::render('tenant/Dashboard', [
      'workspace' => [
        'uuid' => $workspace?->uuid,
        'slug' => $workspace?->slug,
        'name' => $workspace?->name,
      ],
    ]);
  }
}
