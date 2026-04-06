<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    public function dashboard()
    {
        $submissions = Submission::orderBy('created_at', 'desc')->get();

        $stats = [
            'total' => $submissions->count(),
            'graded' => $submissions->where('status', 'graded')->count(),
            'sent' => $submissions->where('status', 'sent')->count(),
            'pending' => $submissions->where('status', 'submitted')->count(),
            'avg_p1' => $submissions->avg('score_p1'),
            'avg_total' => $submissions->whereNotNull('note_total')->avg('note_total'),
        ];

        return view('admin.dashboard', compact('submissions', 'stats'));
    }

    public function show(Submission $submission)
    {
        return view('admin.show', compact('submission'));
    }

    /**
     * Export ALL submissions in a Claude-friendly format for copy-paste.
     */
    public function exportForClaude(Request $request)
    {
        $submissionIds = $request->input('ids');
        $query = Submission::query();

        if ($submissionIds) {
            $query->whereIn('id', explode(',', $submissionIds));
        } else {
            $query->where('status', 'submitted');
        }

        $submissions = $query->orderBy('matricule')->get();

        if ($submissions->isEmpty()) {
            return back()->with('error', 'Aucune copie à exporter.');
        }

        $questions = $this->getQuestionsReference();
        $output = $this->formatForClaude($submissions, $questions);

        return view('admin.export', compact('output', 'submissions'));
    }

    /**
     * Import grades from Claude's response (JSON format).
     */
    public function importGrades(Request $request)
    {
        $request->validate(['grades_json' => 'required|string']);

        $gradesData = json_decode($request->input('grades_json'), true);

        if (!$gradesData || !isset($gradesData['etudiants'])) {
            return back()->with('error', 'Format JSON invalide. Assurez-vous que Claude a retourné le bon format.');
        }

        $updated = 0;
        foreach ($gradesData['etudiants'] as $studentGrade) {
            $submission = Submission::where('matricule', $studentGrade['matricule'])->first();

            if (!$submission) {
                continue;
            }

            $submission->update([
                'note_p2' => $studentGrade['note_p2'] ?? null,
                'note_p3' => $studentGrade['note_p3'] ?? null,
                'note_p4' => $studentGrade['note_p4'] ?? null,
                'note_total' => $studentGrade['note_total'] ?? null,
                'appreciation' => $studentGrade['appreciation'] ?? null,
                'detail_notes' => $studentGrade['details'] ?? null,
                'status' => 'graded',
                'graded_at' => now(),
            ]);
            $updated++;
        }

        return back()->with('success', "$updated copies corrigées avec succès.");
    }

    /**
     * Send grade email to a specific student.
     */
    public function sendGrade(Submission $submission)
    {
        if (!$submission->isGraded()) {
            return back()->with('error', 'Cette copie n\'a pas encore été corrigée.');
        }

        Mail::send('emails.grade', ['submission' => $submission], function ($mail) use ($submission) {
            $mail->to($submission->email, $submission->nom)
                 ->subject("Résultat — Examen Transformation Digitale ({$submission->matricule})");
        });

        $submission->update(['status' => 'sent', 'sent_at' => now()]);

        return back()->with('success', "Note envoyée à {$submission->nom} ({$submission->email}).");
    }

    /**
     * Send grades to all graded students.
     */
    public function sendAllGrades()
    {
        $submissions = Submission::where('status', 'graded')->get();
        $sent = 0;

        foreach ($submissions as $submission) {
            Mail::send('emails.grade', ['submission' => $submission], function ($mail) use ($submission) {
                $mail->to($submission->email, $submission->nom)
                     ->subject("Résultat — Examen Transformation Digitale ({$submission->matricule})");
            });

            $submission->update(['status' => 'sent', 'sent_at' => now()]);
            $sent++;
        }

        return back()->with('success', "$sent notes envoyées avec succès.");
    }

    /**
     * Format submissions for Claude chat - the core feature.
     */
    private function formatForClaude($submissions, $questions): string
    {
        $output = "# CORRECTION D'EXAMEN — Transformation Digitale (Master 2 SRS)\n\n";
        $output .= "## Instructions pour la correction\n\n";
        $output .= "Tu es un correcteur d'examen universitaire expert en Transformation Digitale et Cybersécurité.\n";
        $output .= "Évalue rigoureusement chaque réponse selon le barème indiqué.\n\n";
        $output .= "**IMPORTANT** : Retourne ta correction au format JSON suivant :\n\n";
        $output .= "```json\n";
        $output .= "{\n";
        $output .= "  \"etudiants\": [\n";
        $output .= "    {\n";
        $output .= "      \"matricule\": \"20240001\",\n";
        $output .= "      \"nom\": \"Nom Étudiant\",\n";
        $output .= "      \"note_p2\": 18.5,\n";
        $output .= "      \"note_p3\": 30.0,\n";
        $output .= "      \"note_p4\": 7.0,\n";
        $output .= "      \"note_total\": 80.5,\n";
        $output .= "      \"appreciation\": \"Bon travail dans l'ensemble...\",\n";
        $output .= "      \"details\": {\n";
        $output .= "        \"s1\": {\"note\": 4, \"max\": 5, \"feedback\": \"...\"},\n";
        $output .= "        \"s2\": {\"note\": 3.5, \"max\": 5, \"feedback\": \"...\"},\n";
        $output .= "        \"s3\": {\"note\": 4, \"max\": 5, \"feedback\": \"...\"},\n";
        $output .= "        \"s4\": {\"note\": 3.5, \"max\": 5, \"feedback\": \"...\"},\n";
        $output .= "        \"s5\": {\"note\": 3.5, \"max\": 5, \"feedback\": \"...\"},\n";
        $output .= "        \"c1\": {\"note\": 6, \"max\": 8, \"feedback\": \"...\"},\n";
        $output .= "        \"c2\": {\"note\": 8, \"max\": 10, \"feedback\": \"...\"},\n";
        $output .= "        \"c3\": {\"note\": 9, \"max\": 12, \"feedback\": \"...\"},\n";
        $output .= "        \"c4\": {\"note\": 7, \"max\": 10, \"feedback\": \"...\"},\n";
        $output .= "        \"q4\": {\"note\": 7, \"max\": 10, \"feedback\": \"...\"}\n";
        $output .= "      }\n";
        $output .= "    }\n";
        $output .= "  ]\n";
        $output .= "}\n";
        $output .= "```\n\n";
        $output .= "note_total = score_p1 (déjà calculé) + note_p2 + note_p3 + note_p4\n\n";
        $output .= "---\n\n";

        $output .= "## Référentiel des questions et barèmes\n\n";
        foreach ($questions as $q) {
            $output .= "### {$q['id']} — {$q['title']} ({$q['pts']} pts)\n";
            $output .= "**Question** : {$q['prompt']}\n";
            $output .= "**Barème** : {$q['bareme']}\n\n";
        }

        $output .= "---\n\n";
        $output .= "## Copies des étudiants ({$submissions->count()} copies)\n\n";

        foreach ($submissions as $i => $sub) {
            $n = $i + 1;
            $output .= "### ══════════════════════════════════════\n";
            $output .= "### ÉTUDIANT {$n} : {$sub->nom}\n";
            $output .= "### ══════════════════════════════════════\n\n";
            $output .= "- **Matricule** : {$sub->matricule}\n";
            $output .= "- **Email** : {$sub->email}\n";
            $output .= "- **Groupe** : " . ($sub->groupe ?: 'Non renseigné') . "\n";
            $output .= "- **Score Partie I (auto-corrigé)** : {$sub->score_p1}/25\n";
            $output .= "  - V/F : {$sub->score_vf}/10 | QCM : {$sub->score_qcm}/15\n\n";

            $openAnswers = $sub->open_answers ?? [];

            $output .= "#### PARTIE II — Réponses courtes (25 pts)\n\n";
            foreach (['s1', 's2', 's3', 's4', 's5'] as $qId) {
                $q = collect($questions)->firstWhere('id', $qId);
                $answer = $openAnswers[$qId] ?? '(pas de réponse)';
                $output .= "**{$q['id']}** ({$q['pts']} pts) — {$q['title']}\n";
                $output .= "Réponse :\n```\n{$answer}\n```\n\n";
            }

            $output .= "#### PARTIE III — Étude de cas (40 pts)\n\n";
            foreach (['c1', 'c2', 'c3', 'c4'] as $qId) {
                $q = collect($questions)->firstWhere('id', $qId);
                $answer = $openAnswers[$qId] ?? '(pas de réponse)';
                $output .= "**{$q['id']}** ({$q['pts']} pts) — {$q['title']}\n";
                $output .= "Réponse :\n```\n{$answer}\n```\n\n";
            }

            $output .= "#### PARTIE IV — Synthèse (10 pts)\n\n";
            $q4 = collect($questions)->firstWhere('id', 'q4');
            $answer = $openAnswers['q4'] ?? '(pas de réponse)';
            $output .= "**q4** ({$q4['pts']} pts) — {$q4['title']}\n";
            $output .= "Réponse :\n```\n{$answer}\n```\n\n";

            $output .= "---\n\n";
        }

        return $output;
    }

    private function getQuestionsReference(): array
    {
        return [
            ['id' => 's1', 'title' => 'Question II.1', 'pts' => 5, 'prompt' => 'Expliquez la différence entre Numérisation, Digitalisation et Transformation Digitale. Donnez un exemple concret pour chacun des trois concepts.', 'bareme' => 'Distinction 3 concepts (1pt chacun) + exemples (2pts)'],
            ['id' => 's2', 'title' => 'Question II.2', 'pts' => 5, 'prompt' => "Qu'est-ce que le principe de Security by Design ? Pourquoi est-il préférable d'intégrer la sécurité dès la conception plutôt qu'après le déploiement ? Illustrez avec un exemple.", 'bareme' => 'Définition Security by Design (2pts) + argument (2pts) + exemple (1pt)'],
            ['id' => 's3', 'title' => 'Question II.3', 'pts' => 5, 'prompt' => 'Définissez l\'IAM. Présentez trois mécanismes concrets d\'IAM et expliquez le rôle du principe du moindre privilège.', 'bareme' => 'Définition IAM (1pt) + 3 mécanismes (3pts) + moindre privilège (1pt)'],
            ['id' => 's4', 'title' => 'Question II.4', 'pts' => 5, 'prompt' => "Qu'est-ce que le RGPD ? Citez trois obligations qu'il impose aux organisations et précisez les sanctions en cas de non-conformité.", 'bareme' => 'Définition RGPD (1pt) + 3 obligations (3pts) + sanctions (1pt)'],
            ['id' => 's5', 'title' => 'Question II.5', 'pts' => 5, 'prompt' => "Expliquez le modèle Lean Startup (Build-Measure-Learn). En quoi est-il adapté à l'innovation dans une grande organisation se transformant digitalement ?", 'bareme' => 'Description Build-Measure-Learn (2pts) + pertinence grande organisation (2pts) + exemple (1pt)'],
            ['id' => 'c1', 'title' => 'Question III.1', 'pts' => 8, 'prompt' => 'Réalisez un audit de maturité digitale d\'AlgérieAssure SA en évaluant : Stratégie & Gouvernance, Cybersécurité, Expérience Client, Processus & Opérations (échelle 1–5). Justifiez chaque note.', 'bareme' => '4 axes (2pts chacun) : note justifiée + cohérence contexte'],
            ['id' => 'c2', 'title' => 'Question III.2', 'pts' => 10, 'prompt' => 'Identifiez et cotez les 4 risques les plus critiques. Pour chacun : stratégie de traitement + mesure concrète.', 'bareme' => '4 risques (2,5pts chacun) : cotation + stratégie + mesure'],
            ['id' => 'c3', 'title' => 'Question III.3', 'pts' => 12, 'prompt' => 'Proposez une architecture SI sécurisée : choix cloud justifié, composants sécurité essentiels (IAM, chiffrement, SIEM, pare-feu), approche Zero Trust.', 'bareme' => 'Choix cloud justifié (3pts) + composants sécu (6pts) + Zero Trust (3pts)'],
            ['id' => 'c4', 'title' => 'Question III.4', 'pts' => 10, 'prompt' => 'Proposez une roadmap 24 mois en 3 phases : actions prioritaires, livrables, KPIs + plan conduite du changement.', 'bareme' => '3 phases (6pts) + livrables/KPIs (2pts) + conduite changement (2pts)'],
            ['id' => 'q4', 'title' => 'Question IV', 'pts' => 10, 'prompt' => 'La cybersécurité est-elle un frein ou un accélérateur de la transformation digitale ? Argumentez avec Security by Design, Zero Trust, DevSecOps, ISO/NIST et des exemples concrets.', 'bareme' => 'Position argumentée (2pts) + concepts cours (4pts) + exemples (2pts) + rédaction (2pts)'],
        ];
    }
}
