<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_user_id')->unique();
            $table->string('telegram_username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->enum('state', [
                'idle',
                'awaiting_description',
                'awaiting_location',
                'awaiting_photo',
                'confirming',
                'completed',
            ])->default('idle');
            $table->json('session_data')->nullable(); // stores in-progress complaint data
            $table->foreignId('last_complaint_id')->nullable()->constrained('complaints')->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('telegram_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_sessions');
    }
};
