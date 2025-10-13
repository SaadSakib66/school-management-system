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
        Schema::table('schools', function (Blueprint $t) {
            // Add columns only if they do not already exist
            if (!Schema::hasColumn('schools', 'eiin_num')) {
                $t->string('eiin_num', 50)->nullable()->after('short_name');
            }

            if (!Schema::hasColumn('schools', 'category')) {
                $t->string('category', 100)->nullable()->after('eiin_num');
            }

            if (!Schema::hasColumn('schools', 'website')) {
                $t->string('website', 255)->nullable()->after('logo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $t) {
            // Drop columns only if they exist
            if (Schema::hasColumn('schools', 'eiin_num')) {
                $t->dropColumn('eiin_num');
            }
            if (Schema::hasColumn('schools', 'category')) {
                $t->dropColumn('category');
            }
            if (Schema::hasColumn('schools', 'website')) {
                $t->dropColumn('website');
            }
        });
    }
};
