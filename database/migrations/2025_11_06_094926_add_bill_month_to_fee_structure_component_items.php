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
        Schema::table('fee_structure_component_items', function (Blueprint $table) {
            //// 1 = January ... 12 = December
            $table->tinyInteger('bill_month')->nullable()->after('include_in_monthly')->comment('If set (1-12), add only when generating invoice for that month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_structure_component_items', function (Blueprint $table) {
            //
            $table->dropColumn('bill_month');
        });
    }
};
