<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Annual Leave (15 days for 5-day week workers)
    LeaveType::create([
      'name' => 'Annual Leave',
      'description' => 'Standard annual leave entitlement for 5-day week workers',
      'max_days_per_year' => 15,
      'requires_documentation' => false,
      'gender_specific' => false,
      'gender' => 'any',
      'frequency_years' => 1,
      'minimum_notice_days' => 7,
      'allow_negative_balance' => false,
      'pay_percentage' => 100.00,
    ]);

    // Sick Leave (Full Pay - 4 weeks)
    LeaveType::create([
      'name' => 'Sick Leave (Full Pay)',
      'description' => 'Sick leave with full pay for up to 4 weeks',
      'max_days_per_year' => 20,
      'requires_documentation' => true,
      'gender_specific' => false,
      'gender' => 'any',
      'frequency_years' => 1,
      'minimum_notice_days' => 0,
      'allow_negative_balance' => true,
      'pay_percentage' => 100.00,
    ]);

    // Sick Leave (Half Pay - 8 weeks)
    LeaveType::create([
      'name' => 'Sick Leave (Half Pay)',
      'description' => 'Extended sick leave with half pay for up to 8 weeks',
      'max_days_per_year' => 40,
      'requires_documentation' => true,
      'gender_specific' => false,
      'gender' => 'any',
      'frequency_years' => 1,
      'minimum_notice_days' => 0,
      'allow_negative_balance' => true,
      'pay_percentage' => 50.00,
    ]);

    // Maternity Leave (8 weeks every 3 years)
    LeaveType::create([
      'name' => 'Maternity Leave',
      'description' => 'Paid maternity leave for female employees',
      'max_days_per_year' => 40,
      'requires_documentation' => true,
      'gender_specific' => true,
      'gender' => 'female',
      'frequency_years' => 3,
      'minimum_notice_days' => 14,
      'allow_negative_balance' => false,
      'pay_percentage' => 100.00,
    ]);

    // Paternity Leave (2 weeks every 3 years)
    LeaveType::create([
      'name' => 'Paternity Leave',
      'description' => 'Paid paternity leave for male employees',
      'max_days_per_year' => 10,
      'requires_documentation' => false,
      'gender_specific' => true,
      'gender' => 'male',
      'frequency_years' => 3,
      'minimum_notice_days' => 14,
      'allow_negative_balance' => false,
      'pay_percentage' => 100.00,
    ]);
  }
}
