<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ----- Helper: first school id (fallback) -----
        $firstSchoolId = Schema::hasTable('schools')
            ? (DB::table('schools')->min('id') ?? 1)
            : 1;

        /* ============================================================
         * classes
         * ============================================================ */
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                if (!Schema::hasColumn('classes', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index('school_id', 'classes_school_id_idx');
                }
            });

            // Backfill via users.class_id when possible
            if (Schema::hasTable('users') &&
                Schema::hasColumn('users', 'class_id') &&
                Schema::hasColumn('users', 'school_id')) {

                DB::statement("
                    UPDATE classes c
                    JOIN (
                        SELECT class_id, MIN(school_id) AS school_id
                        FROM users
                        WHERE class_id IS NOT NULL AND school_id IS NOT NULL
                        GROUP BY class_id
                    ) u ON u.class_id = c.id
                    SET c.school_id = u.school_id
                ");
            }
            DB::table('classes')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('classes', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'classes_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * subjects
         * ============================================================ */
        if (Schema::hasTable('subjects')) {
            Schema::table('subjects', function (Blueprint $table) {
                if (!Schema::hasColumn('subjects', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index('school_id', 'subjects_school_id_idx');
                }
            });

            if (Schema::hasTable('class_subjects') &&
                Schema::hasTable('classes') &&
                Schema::hasColumn('class_subjects', 'class_id') &&
                Schema::hasColumn('class_subjects', 'subject_id') &&
                Schema::hasColumn('classes', 'school_id')) {

                DB::statement("
                    UPDATE subjects s
                    JOIN (
                        SELECT cs.subject_id, MIN(c.school_id) AS school_id
                        FROM class_subjects cs
                        JOIN classes c ON c.id = cs.class_id
                        GROUP BY cs.subject_id
                    ) x ON x.subject_id = s.id
                    SET s.school_id = x.school_id
                ");
            }
            DB::table('subjects')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('subjects', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'subjects_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * class_subjects (pivot)
         * ============================================================ */
        if (Schema::hasTable('class_subjects')) {
            Schema::table('class_subjects', function (Blueprint $table) {
                if (!Schema::hasColumn('class_subjects', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id','class_id','subject_id'], 'class_subjects_scope_idx');
                }
            });

            if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'school_id') &&
                Schema::hasColumn('class_subjects', 'class_id')) {
                DB::statement("
                    UPDATE class_subjects cs
                    JOIN classes c ON c.id = cs.class_id
                    SET cs.school_id = c.school_id
                ");
            }
            DB::table('class_subjects')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('class_subjects', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'class_subjects_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * assign_class_teacher
         * ============================================================ */
        if (Schema::hasTable('assign_class_teacher')) {
            Schema::table('assign_class_teacher', function (Blueprint $table) {
                if (!Schema::hasColumn('assign_class_teacher', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id','class_id','teacher_id'], 'assign_class_teacher_scope_idx');
                }
            });

            if (Schema::hasTable('classes') &&
                Schema::hasColumn('classes', 'school_id') &&
                Schema::hasColumn('assign_class_teacher', 'class_id')) {

                DB::statement("
                    UPDATE assign_class_teacher act
                    JOIN classes c ON c.id = act.class_id
                    SET act.school_id = c.school_id
                ");
            }
            DB::table('assign_class_teacher')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('assign_class_teacher', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'assign_class_teacher_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * class_timetables
         * ============================================================ */
        if (Schema::hasTable('class_timetables')) {
            Schema::table('class_timetables', function (Blueprint $table) {
                if (!Schema::hasColumn('class_timetables', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id','class_id'], 'class_timetables_scope_idx');
                }
            });

            if (Schema::hasTable('classes') &&
                Schema::hasColumn('classes', 'school_id') &&
                Schema::hasColumn('class_timetables', 'class_id')) {

                DB::statement("
                    UPDATE class_timetables ct
                    JOIN classes c ON c.id = ct.class_id
                    SET ct.school_id = c.school_id
                ");
            }
            DB::table('class_timetables')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('class_timetables', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'class_timetables_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * exams
         * ============================================================ */
        if (Schema::hasTable('exams')) {
            Schema::table('exams', function (Blueprint $table) {
                if (!Schema::hasColumn('exams', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id'], 'exams_school_id_idx');
                }
            });

            if (Schema::hasColumn('exams', 'class_id') &&
                Schema::hasTable('classes') &&
                Schema::hasColumn('classes', 'school_id')) {

                DB::statement("
                    UPDATE exams e
                    JOIN classes c ON c.id = e.class_id
                    SET e.school_id = c.school_id
                ");
            }
            DB::table('exams')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('exams', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'exams_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * exam_schedules
         * ============================================================ */
        if (Schema::hasTable('exam_schedules')) {
            Schema::table('exam_schedules', function (Blueprint $table) {
                if (!Schema::hasColumn('exam_schedules', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id','exam_id','class_id'], 'exam_schedules_scope_idx');
                }
            });

            if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'school_id') &&
                Schema::hasColumn('exam_schedules', 'class_id')) {

                DB::statement("
                    UPDATE exam_schedules es
                    JOIN classes c ON c.id = es.class_id
                    SET es.school_id = c.school_id
                ");
            }

            if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'school_id') &&
                Schema::hasColumn('exam_schedules', 'exam_id')) {

                DB::statement("
                    UPDATE exam_schedules es
                    JOIN exams e ON e.id = es.exam_id
                    SET es.school_id = e.school_id
                    WHERE es.school_id IS NULL
                ");
            }

            DB::table('exam_schedules')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('exam_schedules', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'exam_schedules_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * marks_registers
         * ============================================================ */
        if (Schema::hasTable('marks_registers')) {
            Schema::table('marks_registers', function (Blueprint $table) {
                if (!Schema::hasColumn('marks_registers', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id'], 'marks_registers_school_id_idx');
                }
            });

            if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'school_id') &&
                Schema::hasColumn('marks_registers', 'class_id')) {
                DB::statement("
                    UPDATE marks_registers mr
                    JOIN classes c ON c.id = mr.class_id
                    SET mr.school_id = c.school_id
                ");
            }

            if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'school_id') &&
                Schema::hasColumn('marks_registers', 'exam_id')) {
                DB::statement("
                    UPDATE marks_registers mr
                    JOIN exams e ON e.id = mr.exam_id
                    SET mr.school_id = e.school_id
                    WHERE mr.school_id IS NULL
                ");
            }

            DB::table('marks_registers')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('marks_registers', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'marks_registers_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * attendances
         * ============================================================ */
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('attendances', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id'], 'attendances_school_id_idx');
                }
            });

            if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'school_id') &&
                Schema::hasColumn('attendances', 'class_id')) {
                DB::statement("
                    UPDATE attendances a
                    JOIN classes c ON c.id = a.class_id
                    SET a.school_id = c.school_id
                ");
            }

            if (Schema::hasTable('users') && Schema::hasColumn('users', 'school_id') &&
                Schema::hasColumn('attendances', 'student_id')) {
                DB::statement("
                    UPDATE attendances a
                    JOIN users u ON u.id = a.student_id
                    SET a.school_id = u.school_id
                    WHERE a.school_id IS NULL
                ");
            }

            DB::table('attendances')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('attendances', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'attendances_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * homeworks
         * ============================================================ */
        if (Schema::hasTable('homeworks')) {
            Schema::table('homeworks', function (Blueprint $table) {
                if (!Schema::hasColumn('homeworks', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id','class_id'], 'homeworks_scope_idx');
                }
            });

            if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'school_id') &&
                Schema::hasColumn('homeworks', 'class_id')) {
                DB::statement("
                    UPDATE homeworks h
                    JOIN classes c ON c.id = h.class_id
                    SET h.school_id = c.school_id
                ");
            }
            DB::table('homeworks')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('homeworks', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'homeworks_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * homework_submissions
         * ============================================================ */
        if (Schema::hasTable('homework_submissions')) {
            Schema::table('homework_submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('homework_submissions', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id','homework_id','student_id'], 'homework_submissions_scope_idx');
                }
            });

            if (Schema::hasTable('homeworks') &&
                Schema::hasColumn('homeworks', 'school_id') &&
                Schema::hasColumn('homework_submissions', 'homework_id')) {
                DB::statement("
                    UPDATE homework_submissions hs
                    JOIN homeworks h ON h.id = hs.homework_id
                    SET hs.school_id = h.school_id
                ");
            }

            if (Schema::hasTable('users') &&
                Schema::hasColumn('users', 'school_id') &&
                Schema::hasColumn('homework_submissions', 'student_id')) {
                DB::statement("
                    UPDATE homework_submissions hs
                    JOIN users u ON u.id = hs.student_id
                    SET hs.school_id = u.school_id
                    WHERE hs.school_id IS NULL
                ");
            }

            DB::table('homework_submissions')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('homework_submissions', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'homework_submissions_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * notices
         * ============================================================ */
        if (Schema::hasTable('notices')) {
            Schema::table('notices', function (Blueprint $table) {
                if (!Schema::hasColumn('notices', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id'], 'notices_school_id_idx');
                }
            });

            if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'school_id') &&
                Schema::hasColumn('notices', 'class_id')) {
                DB::statement("
                    UPDATE notices n
                    JOIN classes c ON c.id = n.class_id
                    SET n.school_id = c.school_id
                ");
            }

            if (Schema::hasColumn('notices', 'created_by') &&
                Schema::hasTable('users') &&
                Schema::hasColumn('users', 'school_id')) {
                DB::statement("
                    UPDATE notices n
                    JOIN users u ON u.id = n.created_by
                    SET n.school_id = u.school_id
                    WHERE n.school_id IS NULL
                ");
            }

            DB::table('notices')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('notices', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'notices_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * email_logs (optional)
         * ============================================================ */
        if (Schema::hasTable('email_logs')) {
            Schema::table('email_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('email_logs', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id'], 'email_logs_school_id_idx');
                }
            });

            if (Schema::hasColumn('email_logs', 'created_by') &&
                Schema::hasTable('users') &&
                Schema::hasColumn('users', 'school_id')) {
                DB::statement("
                    UPDATE email_logs el
                    JOIN users u ON u.id = el.created_by
                    SET el.school_id = u.school_id
                ");
            }

            DB::table('email_logs')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('email_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'email_logs_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });
        }

        /* ============================================================
         * marks_grades (PER-SCHOOL)
         * ============================================================ */
        if (Schema::hasTable('marks_grades')) {
            Schema::table('marks_grades', function (Blueprint $table) {
                if (!Schema::hasColumn('marks_grades', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                    $table->index(['school_id'], 'marks_grades_school_id_idx');
                }
            });

            // Backfill: best-effort (created_by -> users.school_id)
            if (Schema::hasColumn('marks_grades', 'created_by') &&
                Schema::hasTable('users') &&
                Schema::hasColumn('users', 'school_id')) {
                DB::statement("
                    UPDATE marks_grades g
                    JOIN users u ON u.id = g.created_by
                    SET g.school_id = u.school_id
                ");
            }
            DB::table('marks_grades')->whereNull('school_id')->update(['school_id' => $firstSchoolId]);

            Schema::table('marks_grades', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable(false)->change();
                $table->foreign('school_id', 'marks_grades_school_id_fk')->references('id')->on('schools')->cascadeOnDelete();
            });

            // Optional per-school unique on name (change column if yours differs)
            if (Schema::hasColumn('marks_grades', 'name')) {
                $this->addUniqueIfMissing('marks_grades', 'marks_grades_school_name_unique', ['school_id','name']);
            }
        }
    }

    public function down(): void
    {
        // helper to drop FK/index/column if present
        $drop = function (string $table, string $fkName, ?string $indexName = null) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'school_id')) {
                return;
            }
            Schema::table($table, function (Blueprint $t) use ($fkName, $indexName) {
                // drop FK
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $doctrineTable = $sm->listTableDetails($t->getTable());
                if ($doctrineTable->hasForeignKey($fkName)) {
                    $t->dropForeign($fkName);
                }
                // drop index
                if ($indexName) {
                    $indexes = $sm->listTableIndexes($t->getTable());
                    if (array_key_exists($indexName, $indexes)) {
                        $t->dropIndex($indexName);
                    }
                }
                // drop column
                $t->dropColumn('school_id');
            });
        };

        // marks_grades
        if (Schema::hasTable('marks_grades')) {
            if ($this->indexExists('marks_grades', 'marks_grades_school_name_unique')) {
                Schema::table('marks_grades', function (Blueprint $t) {
                    $t->dropUnique('marks_grades_school_name_unique');
                });
            }
        }

        $drop('email_logs',            'email_logs_school_id_fk',            'email_logs_school_id_idx');
        $drop('notices',               'notices_school_id_fk',               'notices_school_id_idx');
        $drop('homework_submissions',  'homework_submissions_school_id_fk',  'homework_submissions_scope_idx');
        $drop('homeworks',             'homeworks_school_id_fk',             'homeworks_scope_idx');
        $drop('attendances',           'attendances_school_id_fk',           'attendances_school_id_idx');
        $drop('marks_registers',       'marks_registers_school_id_fk',       'marks_registers_school_id_idx');
        $drop('exam_schedules',        'exam_schedules_school_id_fk',        'exam_schedules_scope_idx');
        $drop('exams',                 'exams_school_id_fk',                 'exams_school_id_idx');
        $drop('class_timetables',      'class_timetables_school_id_fk',      'class_timetables_scope_idx');
        $drop('assign_class_teacher',  'assign_class_teacher_school_id_fk',  'assign_class_teacher_scope_idx');
        $drop('class_subjects',        'class_subjects_school_id_fk',        'class_subjects_scope_idx');
        $drop('subjects',              'subjects_school_id_fk',              'subjects_school_id_idx');
        $drop('classes',               'classes_school_id_fk',               'classes_school_id_idx');
        $drop('marks_grades',          'marks_grades_school_id_fk',          'marks_grades_school_id_idx');
    }

    private function addUniqueIfMissing(string $table, string $name, array $cols): void
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes($table);
        if (!array_key_exists($name, $indexes)) {
            Schema::table($table, function (Blueprint $t) use ($cols, $name) {
                $t->unique($cols, $name);
            });
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        if (!Schema::hasTable($table)) return false;
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes($table);
        return array_key_exists($name, $indexes);
    }
};
