<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'nid_or_birthcertificate_no')) {
                $table->string('nid_or_birthcertificate_no', 32)
                      ->nullable();
                $table->index('nid_or_birthcertificate_no', 'users_nid_bc_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nid_or_birthcertificate_no')) {
                // Drop the named index first (important on some DBs)
                $table->dropIndex('users_nid_bc_idx');
                $table->dropColumn('nid_or_birthcertificate_no');
            }
        });
    }
};
