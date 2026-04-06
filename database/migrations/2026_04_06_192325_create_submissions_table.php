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
            $table->string('nom');
            $table->string('email');
            $table->string('matricule');
            $table->string('groupe')->nullable();

            // Partie I - auto-graded
            $table->json('answers_vf')->nullable();
            $table->json('answers_qcm')->nullable();
            $table->decimal('score_vf', 5, 2)->default(0);
            $table->decimal('score_qcm', 5, 2)->default(0);
            $table->decimal('score_p1', 5, 2)->default(0);

            // Parties II, III, IV - open answers
            $table->json('open_answers')->nullable();

            // Grades (filled by admin after Claude correction)
            $table->decimal('note_p2', 5, 2)->nullable();
            $table->decimal('note_p3', 5, 2)->nullable();
            $table->decimal('note_p4', 5, 2)->nullable();
            $table->decimal('note_total', 5, 2)->nullable();
            $table->text('appreciation')->nullable();
            $table->json('detail_notes')->nullable();

            $table->enum('status', ['submitted', 'graded', 'sent'])->default('submitted');
            $table->timestamp('graded_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
