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
        Schema::create('email_logs', function (Blueprint $t) {
            $t->id();
            $t->string('role', 20);                 // student|teacher|parent
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('email')->index();
            $t->string('subject');
            $t->longText('body_html')->nullable();
            $t->longText('body_text')->nullable();
            $t->string('status', 20)->default('sent'); // sent|failed|queued
            $t->text('error')->nullable();
            $t->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
