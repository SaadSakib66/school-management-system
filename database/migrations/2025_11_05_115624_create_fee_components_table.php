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
        Schema::create('fee_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');            // e.g. Exam Fee, Transport, Hostel, Service, Event
            $table->string('code')->nullable();// e.g. EXAM, TRANSPORT
            $table->enum('frequency', ['monthly','termly','annual','one_time'])->default('one_time');
            $table->enum('calc_type', ['fixed','percent_of_base'])->default('fixed');
            $table->decimal('default_amount', 12, 2)->nullable();
            $table->boolean('is_optional')->default(false); // student opt-in
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id','status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_components');
    }
};
