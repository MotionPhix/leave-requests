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
        if (!Schema::hasTable('employee_id_settings')) {
            Schema::create('employee_id_settings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->string('prefix')->nullable();
                $table->string('suffix')->nullable();
                $table->string('separator')->default('-');
                $table->boolean('include_year')->default(true);
                $table->string('year_format')->default('Y');
                $table->integer('number_length')->default(4);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_id_settings');
    }
};
