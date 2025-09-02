<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('homework_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('homework_id')->constrained('homeworks')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            $table->longText('text_content')->nullable();   // Summernote HTML
            $table->string('attachment')->nullable();       // public disk path

            $table->timestamp('submitted_at')->nullable();  // first submit time
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['homework_id','student_id'], 'homework_student_unique');
            $table->index(['student_id','submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_submissions');
    }
};
