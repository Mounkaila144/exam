<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->string('student_email');
            $table->string('student_name');
            $table->string('student_matricule')->nullable();
            $table->string('student_group')->nullable();
            $table->string('access_token', 64)->unique();
            $table->timestamp('opened_at')->nullable();
            $table->boolean('locked')->default(false);
            $table->string('locked_reason', 100)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->unique(['exam_id', 'student_email']);
            $table->index('access_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_assignments');
    }
};
