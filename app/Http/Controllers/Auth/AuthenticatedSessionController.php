<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        $workspaces = $user->workspaces;

        // If user has no workspaces, redirect to central workspace list
        if ($workspaces->isEmpty()) {
            return redirect()->route('workspaces.index')->with('info', 'Please select or create a workspace to continue.');
        }

        // If user has only one workspace, redirect directly to it
        if ($workspaces->count() === 1) {
            $workspace = $workspaces->first();

            return redirect()->intended(route('tenant.dashboard', [
                'tenant_slug' => $workspace->slug,
                'tenant_uuid' => $workspace->uuid,
            ]));
        }

        // If user has multiple workspaces, show them in central system
        return redirect()->intended(route('workspaces.index'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
