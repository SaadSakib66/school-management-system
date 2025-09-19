<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Check if an index exists on a table (by exact index name). */
    protected function indexExists(string $table, string $index): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$db, $table, $index]
        );
        return (bool) $row;
    }

    /** Return FK name for a given table+column, or null if none. */
    protected function fkNameByColumn(string $table, string $column): ?string
    {
        $db  = DB::getDatabaseName();
        $row = DB::selectOne("
            SELECT tc.CONSTRAINT_NAME AS name
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
            JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
              ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
             AND tc.TABLE_SCHEMA   = kcu.TABLE_SCHEMA
             AND tc.TABLE_NAME     = kcu.TABLE_NAME
            WHERE tc.TABLE_SCHEMA = ?
              AND tc.TABLE_NAME   = ?
              AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
              AND kcu.COLUMN_NAME = ?
            LIMIT 1
        ", [$db, $table, $column]);

        return $row->name ?? null;
    }

    public function up(): void
    {
        // 0) Drop FK only if it exists (prevents the 1091 error)
        if ($fk = $this->fkNameByColumn('users', 'school_id')) {
            Schema::table('users', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk);
            });
        }

        // 1) Make school_id nullable
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->default(null)->change();
        });

        // 2) Drop any global unique on email if it exists
        if ($this->indexExists('users', 'users_email_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_unique');
            });
        } else {
            // try a couple common fallbacks quietly
            try { DB::statement('ALTER TABLE `users` DROP INDEX `users_email_unique`'); } catch (\Throwable $e) {}
            try { DB::statement('ALTER TABLE `users` DROP INDEX `email`'); } catch (\Throwable $e) {}
        }

        // 3) Add composite unique per school if not already present
        if (! $this->indexExists('users', 'users_school_email_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['school_id', 'email'], 'users_school_email_unique');
            });
        }

        // 4) Re-add FK only if it doesn't already exist (and allow NULLs)
        if (! $this->fkNameByColumn('users', 'school_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('school_id')
                      ->references('id')->on('schools')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Drop composite unique if present
        if ($this->indexExists('users', 'users_school_email_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_school_email_unique');
            });
        } else {
            try { DB::statement('ALTER TABLE `users` DROP INDEX `users_school_email_unique`'); } catch (\Throwable $e) {}
        }

        // Try to restore global unique on email
        if (! $this->indexExists('users', 'users_email_unique')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique('email', 'users_email_unique');
                });
            } catch (\Throwable $e) {}
        }

        // Re-wire FK (only if missing)
        if (! $this->fkNameByColumn('users', 'school_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('school_id')
                      ->references('id')->on('schools')
                      ->cascadeOnDelete();
            });
        }
    }
};
