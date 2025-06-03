<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('employee_id_settings', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->string('prefix')->default('EMP'); // e.g., "EMP"
      $table->string('separator')->default('-'); // e.g., "-"
      $table->integer('number_length')->default(4); // e.g., 4 for "0001"
      $table->string('suffix')->nullable(); // optional suffix
      $table->boolean('include_year')->default(false); // whether to include year
      $table->string('year_format')->default('y'); // 'y' for 2 digits, 'Y' for 4 digits
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('employee_id_settings');
  }
};
