<?php

namespace App\Services\Grading;

use App\Domain\Exam\QuestionType;
use App\Models\Exam;
use Illuminate\Support\Collection;

class ClaudeExportFormatter
{
    public function format(Exam $exam, Collection $submissions): string
    {
        $exam->loadMissing('sections.questions');

        $out = "# CORRECTION D'EXAMEN — {$exam->title}\n\n";
        $out .= "## Instructions\n";
        $out .= "Tu es un correcteur universitaire rigoureux. Évalue chaque réponse selon le barème.\n";
        $out .= "Retourne **uniquement** un JSON au format suivant :\n\n";
        $out .= "```json\n";
        $out .= "{\n  \"etudiants\": [\n    {\n      \"matricule\": \"...\",\n      \"email\": \"...\",\n      \"note_total\": 18.5,\n      \"appreciation\": \"...\",\n      \"details\": {\n        \"q123\": {\"note\": 4, \"max\": 5, \"feedback\": \"...\"}\n      }\n    }\n  ]\n}\n";
        $out .= "```\n\n";
        $out .= "**note_total** doit inclure le score auto-calculé (V/F + QCM) déjà renseigné en tête de chaque copie.\n\n";
        $out .= "---\n\n## Référentiel des questions\n\n";

        foreach ($exam->sections as $section) {
            $out .= "### {$section->title}\n";
            foreach ($section->questions as $q) {
                $out .= "- **q{$q->id}** ({$q->type->label()}, {$q->points} pts) — {$q->prompt}\n";
                if ($q->bareme_text) {
                    $out .= "  Barème : {$q->bareme_text}\n";
                }
            }
            $out .= "\n";
        }

        $out .= "---\n\n## Copies\n\n";

        foreach ($submissions as $i => $submission) {
            $a = $submission->assignment;
            $idx = $i + 1;
            $out .= "### ──── Étudiant {$idx} : {$a->student_name} ────\n";
            $out .= "- **Matricule** : {$a->student_matricule}\n";
            $out .= "- **Email** : {$a->student_email}\n";
            $out .= "- **Score auto (V/F + QCM)** : ".(number_format((float) $submission->auto_score, 2))."\n\n";

            foreach ($exam->sections as $section) {
                $out .= "#### {$section->title}\n";
                foreach ($section->questions as $q) {
                    if ($q->type->isAutoGradable()) continue;
                    $answer = $submission->answers[(string) $q->id] ?? '(pas de réponse)';
                    $out .= "**q{$q->id}** ({$q->points} pts) — {$q->prompt}\n";
                    $out .= "Réponse :\n```\n".(is_string($answer) ? $answer : json_encode($answer))."\n```\n\n";
                }
            }

            $out .= "---\n\n";
        }

        return $out;
    }
}
