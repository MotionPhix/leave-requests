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
    Schema::create('departments', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->string('name');
      $table->string('code')->unique();
      $table->text('description')->nullable();
      $table->foreignId('parent_id')->nullable()
        ->constrained('departments')
        ->nullOnDelete();
      $table->foreignId('manager_id')->nullable()
        ->constrained('users')
        ->nullOnDelete();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });

    // Add foreign key to users table after departments table exists
    Schema::table('users', function (Blueprint $table) {
      $table->foreign('department')
        ->references('id')
        ->on('departments')
        ->nullOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropForeign(['department']);
    });

    Schema::dropIfExists('departments');
  }
};
