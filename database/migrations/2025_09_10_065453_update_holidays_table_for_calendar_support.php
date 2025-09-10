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
        Schema::table('holidays', function (Blueprint $table) {
            // Add start_date and end_date columns for multi-day holiday support
            $table->date('start_date')->nullable()->after('date');
            $table->date('end_date')->nullable()->after('start_date');
            
            // Add color field for calendar display
            $table->string('color', 7)->default('#ef4444')->after('description');
            
            // Add visibility and recurrence fields
            $table->boolean('is_visible_to_employees')->default(true)->after('color');
            $table->string('recurrence_pattern')->nullable()->after('is_recurring'); // yearly, monthly, etc.
        });

        // Migrate existing single date to start_date for backward compatibility
        \DB::statement('UPDATE holidays SET start_date = date, end_date = date WHERE start_date IS NULL');
        
        // Make start_date required after migration
        Schema::table('holidays', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'color', 'is_visible_to_employees', 'recurrence_pattern']);
        });
    }
};
