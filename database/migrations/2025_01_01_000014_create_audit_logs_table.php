<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->string('event');            // created, analyzed, routed, assigned, corrected, resolved
            $table->string('actor_type')->nullable(); // system, bot, officer, operator
            $table->string('actor_id')->nullable();
            $table->text('description')->nullable();

            // AI audit data
            $table->string('ai_model')->nullable();
            $table->decimal('ai_confidence', 5, 4)->nullable();
            $table->json('ai_input')->nullable();
            $table->json('ai_output')->nullable();

            // Human correction
            $table->string('previous_value')->nullable();
            $table->string('new_value')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['complaint_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
