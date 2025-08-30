<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('notice_date')->nullable();
            $table->date('publish_date')->nullable();

            // Comma-separated targets like: "student,teacher,parent"
            $table->string('message_to', 100)->nullable();

            // Your Blade has a message editor; keep HTML here.
            $table->longText('message')->nullable();

            // creator (fixed the obvious typo: created_by)
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();      // created_at, updated_at
            $table->softDeletes();     // deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
