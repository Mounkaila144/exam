<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_section_id')->constrained('exam_sections')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->string('type', 20);
            $table->text('prompt');
            $table->decimal('points', 6, 2)->default(1);
            $table->text('bareme_text')->nullable();
            $table->json('autograde_config')->nullable();
            $table->json('choices')->nullable();
            $table->timestamps();
            $table->index(['exam_section_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
