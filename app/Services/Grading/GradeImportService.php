<?php

namespace App\Services\Grading;

use App\Domain\Exam\SubmissionStatus;
use App\Domain\Grading\ScoreCalculator;
use App\Models\Exam;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GradeImportService
{
    /**
     * @return array{updated:int, skipped:int, errors:string[]}
     */
    public function importFromJson(Exam $exam, string $jsonString): array
    {
        $data = json_decode($jsonString, true);

        if (! is_array($data) || ! isset($data['etudiants']) || ! is_array($data['etudiants'])) {
            throw new RuntimeException('JSON invalide : clé "etudiants" manquante.');
        }

        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($exam, $data, &$updated, &$skipped, &$errors) {
            foreach ($data['etudiants'] as $entry) {
                $matricule = $entry['matricule'] ?? null;
                $email = $entry['email'] ?? null;

                $assignment = $exam->assignments()
                    ->when($matricule, fn ($q) => $q->where('student_matricule', $matricule))
                    ->when(! $matricule && $email, fn ($q) => $q->where('student_email', $email))
                    ->first();

                if (! $assignment || ! $assignment->submission) {
                    $skipped++;
                    $errors[] = "Étudiant non trouvé : {$matricule} / {$email}";
                    continue;
                }

                $submission = $assignment->submission;
                $manual = isset($entry['note_total']) ? (float) $entry['note_total'] - (float) ($submission->auto_score ?? 0) : null;

                $submission->update([
                    'manual_score' => $manual,
                    'total_score' => isset($entry['note_total']) ? (float) $entry['note_total'] : ScoreCalculator::total((float) $submission->auto_score, $manual),
                    'claude_grade_details' => $entry,
                    'status' => SubmissionStatus::GRADED->value,
                    'graded_at' => now(),
                ]);
                $updated++;
            }
        });

        return compact('updated', 'skipped', 'errors');
    }
}
