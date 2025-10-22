<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    /** width of the numeric tail after YY */
    private int $seqWidth = 4;

    public function up(): void
    {
        // 1) Ensure columns exist
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'admission_number')) {
                $table->string('admission_number', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'roll_number')) {
                $table->string('roll_number', 100)->nullable()->index();
            }
        });

        // 2) Normalize & de-duplicate existing rows per (school_id, class_id)
        $this->normalizeExistingStudentNumbers();

        // 3) Add composite unique indexes (ignore if already present)
        foreach ([
            "ALTER TABLE `users` ADD UNIQUE `uniq_users_school_class_admno` (`school_id`,`class_id`,`admission_number`)",
            "ALTER TABLE `users` ADD UNIQUE `uniq_users_school_class_roll` (`school_id`,`class_id`,`roll_number`)",
        ] as $sql) {
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // 1061 = index already exists; ignore
                if (!str_contains($e->getMessage(), '1061')) {
                    throw $e;
                }
            }
        }
    }

    public function down(): void
    {
        // Drop indexes if they exist; ignore if not
        foreach ([
            "ALTER TABLE `users` DROP INDEX `uniq_users_school_class_admno`",
            "ALTER TABLE `users` DROP INDEX `uniq_users_school_class_roll`",
        ] as $sql) {
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // 1091 = can't drop key; ignore
                if (!str_contains($e->getMessage(), '1091')) {
                    throw $e;
                }
            }
        }
    }

    private function normalizeExistingStudentNumbers(): void
    {
        // Pull all students with the fields we need
        $rows = DB::table('users')
            ->select('id','school_id','class_id','admission_date','created_at','admission_number','roll_number')
            ->where('role', 'student')
            ->orderBy('school_id')->orderBy('class_id')->orderBy('id')
            ->get();

        // Group by (school_id, class_id)
        $groups = [];
        foreach ($rows as $r) {
            $k = $r->school_id . ':' . $r->class_id;
            $groups[$k][] = $r;
        }

        foreach ($groups as $key => $students) {
            // Track used codes PER COLUMN for this (school,class)
            $usedAdm = [];
            $usedRoll = [];

            // Track the next sequence per YY per column
            $nextAdmSeq = [];  // ['25' => 3] means next assign will be 25 0003
            $nextRollSeq = [];

            // Pre-scan: mark already "valid" codes and prime next counters
            foreach ($students as $r) {
                [$yyFromDate, $yy] = $this->yyForRow($r);
                // admission_number
                if ($this->isValidYYNumber($r->admission_number)) {
                    $usedAdm[$r->admission_number] = true;
                    $tail = (int)substr($r->admission_number, 2);
                    $yyKey = substr($r->admission_number, 0, 2);
                    $nextAdmSeq[$yyKey] = max($nextAdmSeq[$yyKey] ?? 0, $tail + 1);
                }
                // roll_number
                if ($this->isValidYYNumber($r->roll_number)) {
                    $usedRoll[$r->roll_number] = true;
                    $tail = (int)substr($r->roll_number, 2);
                    $yyKey = substr($r->roll_number, 0, 2);
                    $nextRollSeq[$yyKey] = max($nextRollSeq[$yyKey] ?? 0, $tail + 1);
                }
            }

            // Second pass: fix/assign any bad or duplicate codes
            foreach ($students as $r) {
                [$yyFromDate, $yy] = $this->yyForRow($r);

                // Fix Admission Number
                $adm = $this->normalizeOrNullIfBad($r->admission_number, $yy);
                if ($adm === null || isset($usedAdm[$adm])) {
                    // Need a fresh unique
                    $seq = $nextAdmSeq[$yy] ?? 1;
                    $adm = $yy . str_pad((string)$seq, $this->seqWidth, '0', STR_PAD_LEFT);
                    while (isset($usedAdm[$adm])) {
                        $seq++;
                        $adm = $yy . str_pad((string)$seq, $this->seqWidth, '0', STR_PAD_LEFT);
                    }
                    $nextAdmSeq[$yy] = $seq + 1;
                }
                $usedAdm[$adm] = true;

                // Fix Roll Number
                $roll = $this->normalizeOrNullIfBad($r->roll_number, $yy);
                if ($roll === null || isset($usedRoll[$roll])) {
                    $seq = $nextRollSeq[$yy] ?? 1;
                    $roll = $yy . str_pad((string)$seq, $this->seqWidth, '0', STR_PAD_LEFT);
                    while (isset($usedRoll[$roll])) {
                        $seq++;
                        $roll = $yy . str_pad((string)$seq, $this->seqWidth, '0', STR_PAD_LEFT);
                    }
                    $nextRollSeq[$yy] = $seq + 1;
                }
                $usedRoll[$roll] = true;

                // Write back if anything changed
                if ($r->admission_number !== $adm || $r->roll_number !== $roll) {
                    DB::table('users')->where('id', $r->id)->update([
                        'admission_number' => $adm,
                        'roll_number'      => $roll,
                        'updated_at'       => now(),
                    ]);
                }
            }
        }
    }

    private function yyForRow(object $r): array
    {
        // prefer admission_date, else created_at, else now
        $base = $r->admission_date ? Carbon::parse($r->admission_date)
             : ($r->created_at ? Carbon::parse($r->created_at) : now());
        $y  = (int) $base->year;
        $yy = substr((string)$y, -2);
        return [$y, $yy];
    }

    private function isValidYYNumber(?string $s): bool
    {
        if (!$s) return false;
        return (bool) preg_match('/^[0-9]{2}[0-9]+$/', $s);
    }

    private function normalizeOrNullIfBad(?string $s, string $yy): ?string
    {
        $s = trim((string)$s);
        if ($s === '') return null;

        // already proper YY + digits
        if (preg_match('/^[0-9]{2}[0-9]+$/', $s)) {
            return $s;
        }

        // if it's pure digits without YY, prefix current yy
        if (preg_match('/^[0-9]+$/', $s)) {
            return $yy . $s;
        }

        // otherwise, consider bad; force regeneration
        return null;
    }
};
