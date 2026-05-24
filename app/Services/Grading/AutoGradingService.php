<?php

namespace App\Services\Grading;

use App\Domain\Exam\AutoGrader;
use App\Domain\Exam\QuestionType;
use App\Domain\Exam\SubmissionStatus;
use App\Domain\Grading\ScoreCalculator;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;

class AutoGradingService
{
    public function grade(Submission $submission): Submission
    {
        $submission->loadMissing('assignment.exam.sections.questions');

        $exam = $submission->assignment->exam;
        $answers = $submission->answers ?? [];

        $total = 0.0;

        foreach ($exam->sections as $section) {
            foreach ($section->questions as $question) {
                if (! $question->type->isAutoGradable() || ! is_array($question->autograde_config)) {
                    continue;
                }
                $answer = $answers[(string) $question->id] ?? null;
                $points = (float) $question->points;

                $total += match ($question->type) {
                    QuestionType::VF => AutoGrader::gradeVf($answer, $question->autograde_config, $points),
                    QuestionType::QCM => AutoGrader::gradeQcm($answer, $question->autograde_config, $points),
                    default => 0,
                };
            }
        }

        DB::transaction(function () use ($submission, $total) {
            $submission->update([
                'auto_score' => $total,
                'total_score' => ScoreCalculator::total($total, $submission->manual_score),
                'status' => SubmissionStatus::AUTO_GRADED->value,
            ]);
        });

        return $submission->fresh();
    }
}
