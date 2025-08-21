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
        if (\Illuminate\Support\Facades\Schema::hasTable('class_timetables')) {
            // Table already exists; just mark this migration as "ran"
            return;
        }

        Schema::create('class_timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('week_id')->constrained('weeks')->cascadeOnDelete();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('room_number', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['class_id','subject_id','week_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_timetables');
    }
};
