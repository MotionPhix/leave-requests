<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    foreach (['departments', 'holidays', 'leave_types', 'leave_requests', 'employee_id_settings'] as $tableName) {
      if (!Schema::hasColumn($tableName, 'workspace_id')) {
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
          $table->unsignedBigInteger('workspace_id')->nullable()->after('id');
          $table->index('workspace_id');
        });
      }
    }
  }

  public function down(): void
  {
    foreach (['departments', 'holidays', 'leave_types', 'leave_requests', 'employee_id_settings'] as $tableName) {
      if (Schema::hasColumn($tableName, 'workspace_id')) {
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
          $table->dropIndex([$tableName . '_workspace_id_index']);
          $table->dropColumn('workspace_id');
        });
      }
    }
  }
};
