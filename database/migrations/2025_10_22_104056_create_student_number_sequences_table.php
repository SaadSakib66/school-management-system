<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedSmallInteger('yy'); // 00..99
            $table->unsignedInteger('last_admission_seq')->default(0);
            $table->unsignedInteger('last_roll_seq')->default(0);
            $table->timestamps();
            $table->unique(['school_id','class_id','yy'], 'uniq_school_class_yy');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('student_number_sequences');
    }
};
