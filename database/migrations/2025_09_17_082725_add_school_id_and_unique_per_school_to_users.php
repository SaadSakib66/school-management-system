<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('id');
                $table->index('school_id');
            }

            // Drop global unique on email if it exists
            try {
                $table->dropUnique('users_email_unique');
            } catch (\Throwable $e) {}

            // Unique per school
            $table->unique(['school_id', 'email'], 'users_school_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try { $table->dropUnique('users_school_email_unique'); } catch (\Throwable $e) {}
            $table->unique('email', 'users_email_unique');

            if (Schema::hasColumn('users', 'school_id')) {
                $table->dropIndex(['school_id']);
                $table->dropColumn('school_id');
            }
        });
    }
};
