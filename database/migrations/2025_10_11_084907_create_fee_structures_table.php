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
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id');
            $table->string('academic_year', 9); // e.g., '2025-2026'
            $table->decimal('monthly_fee', 12, 2);
            $table->date('effective_from')->nullable(); // optional
            $table->date('effective_to')->nullable();   // optional
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id','class_id','academic_year'], 'uniq_fee_struct');
            $table->index(['school_id','class_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
