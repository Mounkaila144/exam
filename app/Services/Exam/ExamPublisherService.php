<?php

namespace App\Services\Exam;

use App\Domain\Exam\ExamStatus;
use App\Jobs\SendAssignmentEmailJob;
use App\Models\Exam;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExamPublisherService
{
    public function publish(Exam $exam): Exam
    {
        $exam->load('sections.questions', 'assignments');

        $errors = $this->validate($exam);
        if (! empty($errors)) {
            throw new RuntimeException(implode(' | ', $errors));
        }

        DB::transaction(function () use ($exam) {
            $exam->update(['status' => ExamStatus::PUBLISHED->value]);
        });

        foreach ($exam->assignments as $assignment) {
            SendAssignmentEmailJob::dispatch($assignment->id);
        }

        return $exam;
    }

    /** @return string[] */
    public function validate(Exam $exam): array
    {
        $errors = [];

        if ($exam->duration_minutes <= 0) {
            $errors[] = 'La durée doit être supérieure à 0.';
        }

        $totalQuestions = $exam->sections->sum(fn ($s) => $s->questions->count());
        if ($totalQuestions === 0) {
            $errors[] = 'L\'examen doit contenir au moins une question.';
        }

        $totalPoints = $exam->sections->sum(fn ($s) => $s->questions->sum('points'));
        if ($totalPoints <= 0) {
            $errors[] = 'Le total des points doit être supérieur à 0.';
        }

        if ($exam->opens_at && $exam->closes_at && $exam->opens_at->gte($exam->closes_at)) {
            $errors[] = 'La date d\'ouverture doit être antérieure à la date de fermeture.';
        }

        if ($exam->assignments->isEmpty()) {
            $errors[] = 'Aucun étudiant n\'est inscrit.';
        }

        return $errors;
    }
}
