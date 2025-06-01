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
    Schema::create('leave_types', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->string('name')->unique();
      $table->text('description')->nullable();
      $table->unsignedInteger('max_days_per_year')->default(0);
      $table->boolean('requires_documentation')->default(false);
      $table->boolean('gender_specific')->default(false);
      $table->enum('gender', ['male', 'female', 'any'])->default('any');
      $table->unsignedInteger('minimum_notice_days')->default(0);
      $table->boolean('allow_negative_balance')->default(false);
      $table->decimal('pay_percentage', 5, 2)->default(100.00);
      $table->unsignedInteger('frequency_years')->default(1);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('leave_types');
  }
};
