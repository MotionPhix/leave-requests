<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;

class HolidayController extends Controller
{
  public function index(): Response
  {
    $holidays = Holiday::orderBy('date')->get();

    return Inertia::render('admin/holidays/Index', [
      'holidays' => $holidays
    ]);
  }

  public function create(): Response
  {
    return Inertia::render('admin/holidays/Create');
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'date' => 'required|date',
      'type' => 'required|string|in:Public Holiday,Company Holiday',
      'description' => 'nullable|string'
    ]);

    Holiday::create($validated);

    return redirect()->route('admin.holidays.index')
      ->with('success', 'Holiday created successfully');
  }

  public function edit(Holiday $holiday): Response
  {
    return Inertia::render('admin/holidays/Edit', [
      'holiday' => $holiday
    ]);
  }

  public function update(Request $request, Holiday $holiday)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'date' => 'required|date',
      'type' => 'required|string|in:Public Holiday,Company Holiday',
      'description' => 'nullable|string'
    ]);

    $holiday->update($validated);

    return redirect()->route('admin.holidays.index')
      ->with('success', 'Holiday updated successfully');
  }

  public function destroy(Holiday $holiday)
  {
    $holiday->delete();

    return redirect()->route('admin.holidays.index')
      ->with('success', 'Holiday deleted successfully');
  }
}
