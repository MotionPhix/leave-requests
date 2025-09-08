<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    // roles table: add workspace_id nullable for shared/global roles too
    if (!Schema::hasColumn('roles', 'workspace_id')) {
      Schema::table('roles', function (Blueprint $table) {
        $table->unsignedBigInteger('workspace_id')->nullable()->after('id');
        $table->index('workspace_id');
      });
    }

    // model_has_roles
    if (!Schema::hasColumn('model_has_roles', 'workspace_id')) {
      Schema::table('model_has_roles', function (Blueprint $table) {
        $table->unsignedBigInteger('workspace_id')->nullable()->after('role_id');
        $table->index(['workspace_id', 'model_id', 'model_type'], 'mhr_workspace_model_index');
      });
    }

    // model_has_permissions
    if (!Schema::hasColumn('model_has_permissions', 'workspace_id')) {
      Schema::table('model_has_permissions', function (Blueprint $table) {
        $table->unsignedBigInteger('workspace_id')->nullable()->after('permission_id');
        $table->index(['workspace_id', 'model_id', 'model_type'], 'mhp_workspace_model_index');
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasColumn('roles', 'workspace_id')) {
      Schema::table('roles', function (Blueprint $table) {
        $table->dropColumn('workspace_id');
      });
    }
    if (Schema::hasColumn('model_has_roles', 'workspace_id')) {
      Schema::table('model_has_roles', function (Blueprint $table) {
        $table->dropIndex('mhr_workspace_model_index');
        $table->dropColumn('workspace_id');
      });
    }
    if (Schema::hasColumn('model_has_permissions', 'workspace_id')) {
      Schema::table('model_has_permissions', function (Blueprint $table) {
        $table->dropIndex('mhp_workspace_model_index');
        $table->dropColumn('workspace_id');
      });
    }
  }
};
