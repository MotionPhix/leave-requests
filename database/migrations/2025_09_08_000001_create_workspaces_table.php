<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('workspaces', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->string('name');
      $table->string('slug')->unique();
      $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
      $table->timestamps();
    });

    Schema::create('workspace_user', function (Blueprint $table) {
      $table->id();
      $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('role')->default('member');
      $table->timestamps();
      $table->unique(['workspace_id', 'user_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('workspace_user');
    Schema::dropIfExists('workspaces');
  }
};
