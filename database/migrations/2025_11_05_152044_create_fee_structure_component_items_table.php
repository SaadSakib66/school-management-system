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
        Schema::create('fee_structure_component_items', function (Blueprint $table) {
            $table->id();
            // FK fields
            $table->unsignedBigInteger('fee_structure_id')->index();
            $table->unsignedBigInteger('fee_component_id')->index();

            // optional overrides on pivot
            $table->enum('calc_type_override', ['fixed','percent_of_base'])->nullable();
            $table->decimal('amount_override', 12, 2)->nullable();

            // flags
            $table->boolean('include_in_monthly')->default(false);
            $table->boolean('auto_invoice')->default(false);

            // optional term (null = not term-bound)
            $table->unsignedBigInteger('fee_term_id')->nullable()->index();

            $table->timestamps();

            // (FK constraint চাইলে পরে অন করুন)
            // $table->foreign('fee_structure_id')->references('id')->on('fee_structures')->onDelete('cascade');
            // $table->foreign('fee_component_id')->references('id')->on('fee_components')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_structure_component_items');
    }
};
