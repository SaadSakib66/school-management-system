<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('homeworks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')->constrained('classes')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnUpdate()->restrictOnDelete();

            $table->date('homework_date');
            $table->date('submission_date')->nullable();

            // relative path on "public" disk, e.g. homeworks/uuid.ext
            $table->string('document_file')->nullable();

            $table->longText('description')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_id','subject_id','homework_date'], 'homeworks_cls_subj_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homeworks');
    }
};
