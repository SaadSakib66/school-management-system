<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            //
            $table->decimal('annual_fee', 10, 2)->nullable()->after('monthly_fee');
        });
        // Backfill for existing rows: annual = monthly * 12 (only where annual is null)
        DB::statement('UPDATE fee_structures SET annual_fee = monthly_fee * 12 WHERE annual_fee IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            //
            $table->dropColumn('annual_fee');
        });
    }
};
