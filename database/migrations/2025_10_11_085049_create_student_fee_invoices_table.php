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
        Schema::create('student_fee_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->string('academic_year', 9);
            $table->unsignedTinyInteger('month'); // 1..12
            $table->date('due_date')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('fine', 12, 2)->default(0);
            $table->string('status', 10)->default('unpaid'); // unpaid|partial|paid
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id','student_id','academic_year','month'], 'uniq_invoice_student_month');

            $table->index(
                ['school_id','class_id','academic_year','month'],'sfi_scid_cid_year_mon_idx');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_fee_invoices');
    }
};
