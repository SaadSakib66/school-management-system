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
    public function up()
    {
        Schema::create('student_guardians', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('school_id')->index();
            $t->unsignedBigInteger('student_id')->index(); // users.id where role = 'student'
            $t->unsignedBigInteger('parent_id')->index();  // users.id where role = 'parent'
            $t->string('relationship', 20)->nullable();     // 'mother','father','guardian', etc.
            $t->boolean('is_primary')->default(false);
            $t->timestamps();

            $t->unique(['school_id','student_id','parent_id'], 'uniq_sg_school_student_parent');
        });

        // Backfill from users.parent_id (if exists)
        if (Schema::hasColumn('users', 'parent_id')) {
            // Insert existing links where role=student and parent_id is set
            DB::statement("
                INSERT IGNORE INTO student_guardians (school_id, student_id, parent_id, relationship, is_primary, created_at, updated_at)
                SELECT u.school_id, u.id AS student_id, u.parent_id AS parent_id, NULL AS relationship, 1 AS is_primary, NOW(), NOW()
                FROM users u
                WHERE u.role = 'student' AND u.parent_id IS NOT NULL
            ");
        }
    }

    public function down()
    {
        Schema::dropIfExists('student_guardians');
    }
};
