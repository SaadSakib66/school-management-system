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
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('admission_number')->nullable()->after('last_name');
            $table->string('roll_number')->nullable()->after('admission_number');
            $table->unsignedBigInteger('class_id')->nullable()->after('roll_number');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('class_id');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('religion')->nullable()->after('date_of_birth');
            $table->string('mobile_number')->nullable()->after('religion');
            $table->date('admission_date')->nullable()->after('mobile_number');
            $table->string('student_photo')->nullable()->after('admission_date');
            $table->string('blood_group')->nullable()->after('student_photo');
            $table->string('height')->nullable()->after('blood_group');
            $table->string('weight')->nullable()->after('height');
            $table->boolean('status')->default(1)->after('weight'); // 1=Active,0=Inactive
            $table->string('admin_photo')->nullable()->after('status');
            $table->string('teacher_photo')->nullable()->after('admin_photo');
            $table->string('parent_photo')->nullable()->after('teacher_photo');
            $table->softDeletes();

            $table->foreign('class_id')
                  ->references('id')
                  ->on('classes')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropColumn([
                'last_name',
                'admission_number',
                'roll_number',
                'class_id',
                'gender',
                'date_of_birth',
                'religion',
                'mobile_number',
                'admission_date',
                'student_photo',
                'blood_group',
                'height',
                'weight',
                'status',
                'admin_photo',
                'teacher_photo',
                'parent_photo',
            ]);
        });
    }
};
