<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('category');         // ketertiban, kebakaran, sampah, lalu_lintas
            $table->string('subcategory')->nullable();
            $table->enum('priority_override', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->integer('sla_minutes')->nullable(); // override agency default
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_rules');
    }
};
