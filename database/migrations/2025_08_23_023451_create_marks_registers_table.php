<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marks_registers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            $table->unsignedInteger('class_work')->nullable();
            $table->unsignedInteger('home_work')->nullable();
            $table->unsignedInteger('test_work')->nullable();
            $table->unsignedInteger('exam_mark')->nullable();

            $table->unsignedInteger('total')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['exam_id','class_id','student_id','subject_id'],
                'uk_marks_unique'
            );
            $table->index(['exam_id','class_id','subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marks_registers');
    }
};

