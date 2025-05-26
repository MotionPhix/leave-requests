<?php

use App\Enums\LeaveStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('leave_requests', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
      $table->date('start_date');
      $table->date('end_date');
      $table->text('reason')->nullable();
      $table->string('status')->default(LeaveStatus::Pending->value);
      $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
      $table->text('comment')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('leave_requests');
  }
};
