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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['meeting', 'announcement', 'training', 'social', 'other'])->default('announcement');
            $table->string('location')->nullable();
            $table->string('color', 7)->default('#3b82f6'); // Hex color for calendar display
            
            $table->date('start_date');
            $table->date('end_date')->nullable(); // For multi-day events
            $table->time('start_time')->nullable(); // For timed events
            $table->time('end_time')->nullable();
            $table->boolean('all_day')->default(true);
            
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_data')->nullable(); // Store recurrence rules
            
            $table->json('attendees')->nullable(); // Array of user IDs who should attend
            $table->boolean('is_mandatory')->default(false); // Whether attendance is required
            
            $table->timestamps();
            
            // Indexes
            $table->index(['workspace_id', 'start_date']);
            $table->index(['workspace_id', 'type']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
