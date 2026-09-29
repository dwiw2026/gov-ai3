<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 30)->unique(); // CA-2026-00001
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained()->nullOnDelete();

            // Citizen info
            $table->string('channel', 20)->default('telegram'); // telegram, web, bob
            $table->string('citizen_identifier')->nullable(); // masked telegram id / web session
            $table->string('citizen_name')->nullable();

            // Raw input
            $table->text('raw_message');
            $table->string('image_path')->nullable();
            $table->string('location_text')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // AI Analysis (from Langflow)
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->decimal('ai_confidence', 5, 4)->nullable(); // 0.0000 - 1.0000
            $table->text('ai_reason')->nullable();
            $table->text('ai_recommended_action')->nullable();
            $table->json('ai_raw_output')->nullable(); // full Langflow JSON
            $table->boolean('requires_human_review')->default(false);

            // Status
            $table->enum('status', [
                'pending',          // just received
                'analyzing',        // Langflow processing
                'awaiting_review',  // AI confidence < threshold
                'routed',           // assigned to agency
                'assigned',         // assigned to officer
                'in_progress',      // officer working on it
                'resolved',         // completed
                'rejected',         // invalid report
            ])->default('pending');

            // Human corrections (Responsible AI)
            $table->string('corrected_category')->nullable();
            $table->string('corrected_agency_id')->nullable();
            $table->text('correction_notes')->nullable();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();

            // SLA
            $table->timestamp('sla_deadline')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['agency_id', 'status']);
            $table->index('ticket_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
