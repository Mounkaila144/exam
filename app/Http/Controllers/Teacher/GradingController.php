<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ImportGradesRequest;
use App\Jobs\DispatchClaudeGradingJob;
use App\Jobs\SendBulkGradeEmailsJob;
use App\Jobs\SendGradeEmailJob;
use App\Models\Exam;
use App\Models\Submission;
use App\Services\Grading\ClaudeExportFormatter;
use App\Services\Grading\GradeImportService;
use App\Services\Security\PlatformApiKeyVault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class GradingController extends Controller
{
    public function __construct(
        private readonly ClaudeExportFormatter $formatter,
        private readonly GradeImportService $importer,
        private readonly PlatformApiKeyVault $vault,
    ) {
    }

    public function show(Exam $exam): View
    {
        $this->authorize('view', $exam);

        $assignments = $exam->assignments()
            ->with('submission')
            ->orderBy('student_name')
            ->get();

        return view('teacher.exams.grading', [
            'exam' => $exam,
            'assignments' => $assignments,
            'hasApiKey' => $this->vault->hasKey(),
        ]);
    }

    public function exportForClaude(Exam $exam): View
    {
        $this->authorize('view', $exam);

        $exam->load(['assignments.submission', 'sections.questions']);
        $submissions = $exam->assignments
            ->filter(fn ($a) => $a->submission && in_array($a->submission->status->value, ['submitted', 'auto_graded', 'graded', 'sent']))
            ->map->submission;

        $markdown = $this->formatter->format($exam, $submissions);

        return view('teacher.exams.grading-export', [
            'exam' => $exam,
            'markdown' => $markdown,
        ]);
    }

    public function importGrades(ImportGradesRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorize('view', $exam);

        try {
            $result = $this->importer->importFromJson($exam, $request->string('grades_json'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['grades_json' => $e->getMessage()]);
        }

        return redirect()->route('teacher.exams.grading', $exam)
            ->with('success', "{$result['updated']} copies notées, {$result['skipped']} ignorées.");
    }

    public function dispatchClaudeApi(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('view', $exam);

        if (! $this->vault->hasKey()) {
            return back()->withErrors(['claude' => 'Aucune clé API plateforme configurée par l\'administrateur.']);
        }

        DispatchClaudeGradingJob::dispatch($exam->id, $request->user()->id);

        return back()->with('success', 'Correction Claude lancée en arrière-plan. Vous recevrez un email à la fin.');
    }

    public function sendGrade(Submission $submission): RedirectResponse
    {
        $this->authorize('grade', $submission);

        SendGradeEmailJob::dispatch($submission->id);

        return back()->with('success', 'Email programmé.');
    }

    public function sendAllGrades(Exam $exam): RedirectResponse
    {
        $this->authorize('view', $exam);

        SendBulkGradeEmailsJob::dispatch($exam->id);

        return back()->with('success', 'Envoi en masse programmé.');
    }
}
