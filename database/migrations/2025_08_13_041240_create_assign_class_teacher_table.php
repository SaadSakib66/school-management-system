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
        Schema::create('assign_class_teacher', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('status')->default(true); // 1 = Active, 0 = Inactive
            $table->timestamps();
            $table->softDeletes();

            // Performance helpers
            $table->index(['class_id', 'teacher_id']);
            $table->index('status');

            // Prevent duplicate class/teacher pairs while allowing re-create after soft delete
            $table->unique(['class_id', 'teacher_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assign_class_teacher');
    }
};
