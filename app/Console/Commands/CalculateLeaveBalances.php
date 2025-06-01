<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LeaveBalanceService;
use Illuminate\Console\Command;

class CalculateLeaveBalances extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'leave:calculate-balances {user? : Optional user ID}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Calculate leave balances for users';

  /**
   * Execute the console command.
   */
  public function handle(LeaveBalanceService $leaveBalanceService): void
  {
    $users = $this->argument('user')
      ? User::whereId($this->argument('user'))->get()
      : User::all();

    $this->withProgressBar($users, function ($user) use ($leaveBalanceService) {
      foreach ($user->company->leaveTypes as $leaveType) {
        $available = $leaveBalanceService->getRemainingDays($user->id, $leaveType->id);
        $this->line("\n{$user->name} has {$available} days of {$leaveType->name} remaining");
      }
    });

    $this->info("\nLeave balances calculated successfully!");
  }
}
