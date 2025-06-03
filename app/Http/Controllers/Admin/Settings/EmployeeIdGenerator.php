<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\EmployeeIdSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmployeeIdGenerator extends Controller
{
  public function edit()
  {
    return Inertia::render('admin/settings/EmployeeId', [
      'settings' => EmployeeIdSetting::first() ?? new EmployeeIdSetting([
        'prefix' => 'EMP',
        'separator' => '-',
        'number_length' => 4,
        'suffix' => null,
        'include_year' => false,
        'year_format' => 'y'
      ])
    ]);
  }

  public function update(Request $request)
  {
    $validated = $request->validate([
      'prefix' => [
        'required',
        'string',
        'max:10',
        'regex:/^[A-Za-z]+$/'
      ],
      'separator' => [
        'required',
        'string',
        'max:1',
        Rule::in(['-', '_', '/', '.'])
      ],
      'number_length' => [
        'required',
        'integer',
        'min:1',
        'max:10'
      ],
      'suffix' => [
        'nullable',
        'string',
        'max:10',
        'regex:/^[A-Za-z0-9]+$/'
      ],
      'include_year' => [
        'required',
        'boolean'
      ],
      'year_format' => [
        'required',
        'string',
        Rule::in(['y', 'Y'])
      ],
    ], [
      'prefix.required' => 'The prefix is required.',
      'prefix.max' => 'The prefix cannot be longer than 10 characters.',
      'prefix.regex' => 'The prefix must contain only letters.',

      'separator.required' => 'The separator is required.',
      'separator.max' => 'The separator must be a single character.',
      'separator.in' => 'The separator must be one of: hyphen (-), underscore (_), forward slash (/), or dot (.)',

      'number_length.required' => 'The number length is required.',
      'number_length.integer' => 'The number length must be a whole number.',
      'number_length.min' => 'The number length must be at least 1.',
      'number_length.max' => 'The number length cannot be greater than 10.',

      'suffix.max' => 'The suffix cannot be longer than 10 characters.',
      'suffix.regex' => 'The suffix must contain only letters and numbers.',

      'include_year.required' => 'Please specify whether to include the year.',
      'include_year.boolean' => 'The include year field must be true or false.',

      'year_format.required' => 'The year format is required when including year.',
      'year_format.in' => 'The year format must be either 2 digits (y) or 4 digits (Y).',
    ]);

    try {
      $settings = EmployeeIdSetting::first() ?? new EmployeeIdSetting();
      $settings->fill($validated)->save();

      return back()->with('success', 'Employee ID settings updated successfully.');
    } catch (\Exception $e) {
      return back()->withErrors([
        'error' => 'Failed to update employee ID settings. Please try again.'
      ]);
    }
  }
}
