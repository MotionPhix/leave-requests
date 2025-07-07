<?php

namespace App\Http\Controllers\Employee\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
  /**
   * Show the user's profile settings page.
   */
  public function edit(Request $request): Response
  {
    $user = $request->user();

    // Load relationships
    $user->load(['manager', 'departmentModel']);

    // Get available office locations (you can customize this based on your needs)
    $officeLocations = [
      'Main Office - Downtown',
      'Branch Office - North',
      'Branch Office - South',
      'Remote',
      'Hybrid',
      'Field Office',
      'Client Site',
    ];

    // Get available employment types (you can customize this based on your needs)
    $employmentTypes = [
      'Full-time',
      'Part-time',
      'Contract',
      'Internship',
      'Freelance',
      'Temporary',
      'Remote',
    ];

    // Get available job titles (you can customize this based on your needs)
    $jobTitles = [
      'Software Engineer',
      'Data Analyst',
      'Project Manager',
      'Sales Representative',
      'Marketing Specialist',
      'Customer Support Agent',
      'HR Manager',
      'Finance Officer',
    ];

    return Inertia::render('employee/settings/Profile', [
      'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
      'status' => $request->session()->get('status'),
      'officeLocations' => $officeLocations,
      'user' => $user,
    ]);
  }

  /**
   * Update the user's profile information.
   */
  public function update(ProfileUpdateRequest $request): RedirectResponse
  {
    $request->user()->fill($request->validated());

    if ($request->user()->isDirty('email')) {
      $request->user()->email_verified_at = null;
    }

    $request->user()->save();

    // return to_route('profile.edit');
    return Redirect::route('profile.edit')->with('success', 'Profile updated successfully.');
  }

  /**
   * Delete the user's profile.
   */
  public function destroy(Request $request): RedirectResponse
  {
    $request->validate([
      'password' => ['required', 'current_password'],
    ]);

    $user = $request->user();

    Auth::logout();

    $user->delete();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
  }
}
