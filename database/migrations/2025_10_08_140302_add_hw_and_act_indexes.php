<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            if (! $this->hasIndex($table, 'hw_class_submission_idx')) {
                $table->index(['class_id', 'submission_date'], 'hw_class_submission_idx');
            }
            if (! $this->hasIndex($table, 'hw_school_created_idx')) {
                $table->index(['school_id', 'created_by'], 'hw_school_created_idx');
            }
        });

        Schema::table('assign_class_teacher', function (Blueprint $table) {
            if (! $this->hasIndex($table, 'act_teacher_status_class_idx')) {
                $table->index(['teacher_id', 'status', 'class_id'], 'act_teacher_status_class_idx');
            }
            if (! $this->hasIndex($table, 'act_school_teacher_idx')) {
                $table->index(['school_id', 'teacher_id'], 'act_school_teacher_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropIndex('hw_class_submission_idx');
            $table->dropIndex('hw_school_created_idx');
        });

        Schema::table('assign_class_teacher', function (Blueprint $table) {
            $table->dropIndex('act_teacher_status_class_idx');
            $table->dropIndex('act_school_teacher_idx');
        });
    }

    // Helper because Blueprint doesn't expose index existence
    private function hasIndex(Blueprint $table, string $name): bool
    {
        // Laravel doesn't provide a built-in check here, so return false to keep it simple.
        // If you run into duplicate index errors, drop existing or remove this helper.
        return false;
    }
};
