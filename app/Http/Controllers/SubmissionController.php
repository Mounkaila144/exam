<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    /**
     * API endpoint: receive exam submission from the HTML form.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'studentInfo.nom' => 'required|string|max:255',
            'studentInfo.email' => 'required|email|max:255',
            'studentInfo.matricule' => 'required|string|max:100',
            'studentInfo.groupe' => 'nullable|string|max:50',
            'answers' => 'nullable|array',
            'openAnswers' => 'nullable|array',
            'score1' => 'nullable|numeric',
        ]);

        $student = $request->input('studentInfo');
        $answers = $request->input('answers', []);
        $openAnswers = $request->input('openAnswers', []);
        $score1 = $request->input('score1', 0);

        // Separate VF and QCM answers
        $vfAnswers = [];
        $qcmAnswers = [];
        foreach ($answers as $key => $value) {
            if (str_starts_with($key, 'vf')) {
                $vfAnswers[$key] = $value;
            } elseif (str_starts_with($key, 'qcm')) {
                $qcmAnswers[$key] = $value;
            }
        }

        // Calculate VF and QCM scores
        $vfCorrect = [
            'vf1' => 'FAUX', 'vf2' => 'FAUX', 'vf3' => 'VRAI', 'vf4' => 'VRAI', 'vf5' => 'FAUX',
            'vf6' => 'VRAI', 'vf7' => 'FAUX', 'vf8' => 'FAUX', 'vf9' => 'VRAI', 'vf10' => 'VRAI',
        ];
        $qcmCorrect = ['qcm1' => 'C', 'qcm2' => 'B', 'qcm3' => 'C', 'qcm4' => 'C', 'qcm5' => 'C'];

        $scoreVf = 0;
        foreach ($vfCorrect as $id => $correct) {
            if (isset($vfAnswers[$id])) {
                $scoreVf += ($vfAnswers[$id] === $correct) ? 1 : -0.5;
            }
        }
        $scoreVf = max(0, $scoreVf);

        $scoreQcm = 0;
        foreach ($qcmCorrect as $id => $correct) {
            if (isset($qcmAnswers[$id]) && $qcmAnswers[$id] === $correct) {
                $scoreQcm += 3;
            }
        }

        $submission = Submission::create([
            'nom' => $student['nom'],
            'email' => $student['email'],
            'matricule' => $student['matricule'],
            'groupe' => $student['groupe'] ?? null,
            'answers_vf' => $vfAnswers,
            'answers_qcm' => $qcmAnswers,
            'score_vf' => $scoreVf,
            'score_qcm' => $scoreQcm,
            'score_p1' => $scoreVf + $scoreQcm,
            'open_answers' => $openAnswers,
        ]);

        return response()->json([
            'success' => true,
            'id' => $submission->id,
            'score_p1' => $submission->score_p1,
        ]);
    }
}
