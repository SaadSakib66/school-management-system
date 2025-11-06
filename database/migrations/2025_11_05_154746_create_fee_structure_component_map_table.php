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
        Schema::create('fee_structure_component_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('structure_id');   // fee_structures.id
            $table->unsignedBigInteger('component_id');   // fee_components.id
            $table->enum('calc_type_override', ['fixed','percent_of_base'])->nullable();
            $table->decimal('amount_override', 12, 2)->nullable();
            $table->boolean('include_in_monthly')->default(false);
            $table->boolean('auto_invoice')->default(false);
            $table->unsignedBigInteger('fee_term_id')->nullable(); // fee_terms.id
            $table->timestamps();

            $table->foreign('structure_id')->references('id')->on('fee_structures')->onDelete('cascade');
            $table->foreign('component_id')->references('id')->on('fee_components')->onDelete('cascade');
            $table->foreign('fee_term_id')->references('id')->on('fee_terms')->nullOnDelete();

            $table->unique(['structure_id','component_id']); // প্রত্যেক কম্পোনেন্ট একবার
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_structure_component_map');
    }
};
