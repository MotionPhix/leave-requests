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
    Schema::table('users', function (Blueprint $table) {
      $table->string('position')->nullable();
      $table->unsignedBigInteger('department')->nullable();
      $table->string('employee_id')->nullable()->unique();
      $table->date('join_date')->nullable();
      $table->unsignedBigInteger('reporting_to')->nullable();
      $table->string('work_phone')->nullable();
      $table->string('office_location')->nullable();
      $table->string('employment_status')->default('active');
      $table->string('employment_type')->default('full-time');

      $table->foreign('reporting_to')
        ->references('id')
        ->on('users')
        ->nullOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn([
        'position',
        'department',
        'employee_id',
        'join_date',
        'reporting_to',
        'work_phone',
        'office_location',
        'employment_status',
        'employment_type'
      ]);
    });
  }
};
