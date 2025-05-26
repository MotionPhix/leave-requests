<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('leave_allocations', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
      $table->year('year');
      $table->unsignedInteger('allocated_days')->default(0);
      $table->timestamps();

      $table->unique(['user_id', 'leave_type_id', 'year']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('leave_allocations');
  }
};
