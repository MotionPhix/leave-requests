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
    $holidays = Holiday::query()
      ->orderBy('date')
      ->paginate(10)
      ->through(fn($holiday) => [
        'id' => $holiday->id,
        'uuid' => $holiday->uuid,
        'name' => $holiday->name,
        'type' => $holiday->type,
        'date' => $holiday->date->format('Y-m-d'),
        'description' => $holiday->description,
        'is_recurring' => $holiday->is_recurring,
      ]);

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
      'is_recurring' => 'boolean',
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
      'is_recurring' => 'boolean',
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
