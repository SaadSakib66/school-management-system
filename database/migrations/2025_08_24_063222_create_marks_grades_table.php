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
        Schema::create('marks_grades', function (Blueprint $table) {
            $table->id();
            $table->string('grade_name', 50);
            $table->unsignedTinyInteger('percent_from'); // 0..100
            $table->unsignedTinyInteger('percent_to');   // 0..100
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marks_grades');
    }
};
