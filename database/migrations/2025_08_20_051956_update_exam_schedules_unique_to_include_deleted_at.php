<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_schedules', function (Blueprint $table) {
            // 1) Drop foreign keys so the index can be changed
            $table->dropForeign('exam_schedules_exam_id_foreign');
            $table->dropForeign('exam_schedules_class_id_foreign');
            $table->dropForeign('exam_schedules_subject_id_foreign');

            // 2) Drop old unique
            $table->dropUnique('exam_schedules_exam_id_class_id_subject_id_unique');

            // 3) Add new unique that includes deleted_at
            $table->unique(
                ['exam_id','class_id','subject_id','deleted_at'],
                'exam_schedules_unique_active'
            );

            // 4) Re-add the foreign keys
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_schedules', function (Blueprint $table) {
            // Drop FKs again
            $table->dropForeign('exam_schedules_exam_id_foreign');
            $table->dropForeign('exam_schedules_class_id_foreign');
            $table->dropForeign('exam_schedules_subject_id_foreign');

            // Revert the unique
            $table->dropUnique('exam_schedules_unique_active');
            $table->unique(
                ['exam_id','class_id','subject_id'],
                'exam_schedules_exam_id_class_id_subject_id_unique'
            );

            // Re-add FKs
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }
};
