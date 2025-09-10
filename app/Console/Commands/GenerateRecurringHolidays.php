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

    // Get all recurring holidays grouped by workspace to handle multi-tenant properly
    $recurringHolidays = Holiday::where('is_recurring', true)
      ->whereNotNull('recurrence_pattern')
      ->with('workspace')
      ->get();
    
    if ($recurringHolidays->isEmpty()) {
      $this->warn('No recurring holidays found to generate.');
      return;
    }

    $bar = $this->output->createProgressBar(count($recurringHolidays) * $yearsToGenerate);
    $generated = 0;
    $skipped = 0;

    for ($year = $startYear; $year < $startYear + $yearsToGenerate; $year++) {
      foreach ($recurringHolidays as $holiday) {
        $newStartDate = Carbon::parse($holiday->start_date)->setYear($year);
        $newEndDate = Carbon::parse($holiday->end_date)->setYear($year);

        // Skip if holiday already exists for this date and workspace
        $exists = Holiday::where('workspace_id', $holiday->workspace_id)
          ->where('name', $holiday->name)
          ->whereDate('start_date', $newStartDate)
          ->exists();

        if ($exists) {
          $skipped++;
          $bar->advance();
          continue;
        }

        // Generate new holiday based on recurrence pattern
        $newHoliday = $this->generateRecurringHoliday($holiday, $year, $newStartDate, $newEndDate);
        
        if ($newHoliday) {
          $generated++;
        }

        $bar->advance();
      }
    }

    $bar->finish();
    $this->newLine();
    $this->info("Successfully generated {$generated} recurring holidays!");
    
    if ($skipped > 0) {
      $this->comment("Skipped {$skipped} holidays that already exist.");
    }
    
    $this->displayGenerationSummary($startYear, $yearsToGenerate, $generated);
  }

  /**
   * Generate a recurring holiday for a specific year
   */
  private function generateRecurringHoliday(Holiday $baseHoliday, int $year, Carbon $startDate, Carbon $endDate): ?Holiday
  {
    try {
      // Calculate duration to maintain multi-day holidays
      $originalDuration = $baseHoliday->start_date->diffInDays($baseHoliday->end_date);
      $newEndDate = $startDate->copy()->addDays($originalDuration);

      $newHoliday = Holiday::create([
        'workspace_id' => $baseHoliday->workspace_id,
        'name' => $baseHoliday->name,
        'start_date' => $startDate,
        'end_date' => $newEndDate,
        'date' => $startDate, // Backward compatibility
        'type' => $baseHoliday->type,
        'description' => $baseHoliday->description,
        'color' => $baseHoliday->color,
        'is_recurring' => false, // Generated instances are not recurring
        'recurrence_pattern' => null,
        'is_visible_to_employees' => $baseHoliday->is_visible_to_employees,
      ]);

      return $newHoliday;
    } catch (\Exception $e) {
      $this->error("Failed to generate holiday '{$baseHoliday->name}' for {$year}: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Display generation summary
   */
  private function displayGenerationSummary(int $startYear, int $years, int $generated): void
  {
    $this->newLine();
    $this->line('<fg=cyan>Generation Summary:</>');
    $this->line("• Years: {$startYear} - " . ($startYear + $years - 1));
    $this->line("• Holidays generated: {$generated}");
    $this->line("• Next run: php artisan holidays:generate " . ($startYear + $years));
    
    if ($generated > 0) {
      $this->newLine();
      $this->line('<fg=green>✓</> Recurring holidays have been generated successfully!');
      $this->line('<fg=yellow>💡</> Consider scheduling this command to run annually via Laravel Scheduler.');
    }
  }
}
