<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateRecurringHolidays extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'holidays:generate {year? : The year to generate holidays for} {--years=1 : Number of years to generate}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Generate recurring holidays for future years';

  /**
   * Execute the console command.
   */
  public function handle(): void
  {
    $startYear = $this->argument('year') ?? now()->addYear()->year;
    $yearsToGenerate = $this->option('years');

    $this->info("Generating recurring holidays for {$yearsToGenerate} year(s) starting from {$startYear}");

    $recurringHolidays = Holiday::where('is_recurring', true)->get();
    $bar = $this->output->createProgressBar(count($recurringHolidays) * $yearsToGenerate);

    for ($year = $startYear; $year < $startYear + $yearsToGenerate; $year++) {
      foreach ($recurringHolidays as $holiday) {
        $date = Carbon::parse($holiday->date)->setYear($year);

        // Skip if holiday already exists for this date
        if (Holiday::where('date', $date->format('Y-m-d'))->exists()) {
          $bar->advance();
          continue;
        }

        Holiday::create([
          'name' => $holiday->name,
          'date' => $date,
          'description' => $holiday->description,
          'type' => $holiday->type,
          'is_recurring' => false, // Set to false to prevent infinite recursion
        ]);

        $bar->advance();
      }
    }

    $bar->finish();
    $this->newLine();
    $this->info('Successfully generated recurring holidays!');
  }
}
