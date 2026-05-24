<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_assignment_id')->unique()->constrained('exam_assignments')->cascadeOnDelete();
            $table->json('answers')->nullable();
            $table->decimal('auto_score', 8, 2)->nullable();
            $table->decimal('manual_score', 8, 2)->nullable();
            $table->decimal('total_score', 8, 2)->nullable();
            $table->string('status', 20)->default('in_progress');
            $table->timestamp('graded_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('claude_raw_response')->nullable();
            $table->json('claude_grade_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
