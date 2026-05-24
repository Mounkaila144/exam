<?php

namespace App\Http\Controllers\Teacher;

use App\Domain\Exam\ExamStatus;
use App\Domain\Exam\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreExamRequest;
use App\Http\Requests\Teacher\StoreQuestionRequest;
use App\Http\Requests\Teacher\UpdateExamSecurityRequest;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamBuilderController extends Controller
{
    public function index(Request $request): View
    {
        $exams = $request->user()
            ->exams()
            ->withCount(['assignments', 'questions'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('teacher.exams.index', ['exams' => $exams]);
    }

    public function create(): View
    {
        return view('teacher.exams.create');
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $exam = Exam::create([
            ...$request->validated(),
            'teacher_id' => $request->user()->id,
            'status' => ExamStatus::DRAFT->value,
            'security_settings' => Exam::DEFAULT_SECURITY_SETTINGS,
        ]);

        return redirect()->route('teacher.exams.edit', $exam);
    }

    public function edit(Exam $exam, Request $request): View
    {
        $this->authorize('update', $exam);

        $exam->load('sections.questions');

        return view('teacher.exams.edit', ['exam' => $exam]);
    }

    public function update(StoreExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);
        abort_unless($exam->isDraft(), 422, 'Examen publié — non modifiable.');

        $exam->update($request->validated());

        return back()->with('success', 'Examen mis à jour.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorize('delete', $exam);
        $exam->delete();

        return redirect()->route('teacher.exams.index')->with('success', 'Examen supprimé.');
    }

    public function updateSecurity(UpdateExamSecurityRequest $request, Exam $exam): RedirectResponse
    {
        $exam->update([
            'security_settings' => array_merge(
                Exam::DEFAULT_SECURITY_SETTINGS,
                $request->validated(),
            ),
        ]);

        return back()->with('success', 'Paramètres de sécurité enregistrés.');
    }

    // ---- Sections ----

    public function storeSection(Exam $exam, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $exam);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
        ]);

        $section = $exam->sections()->create([
            ...$data,
            'order' => ($exam->sections()->max('order') ?? -1) + 1,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'data' => $section]);
        }

        return back()->with('success', 'Section ajoutée.');
    }

    public function reorderSections(Exam $exam, Request $request): JsonResponse
    {
        $this->authorize('update', $exam);
        $ids = $request->validate(['order' => ['required', 'array']])['order'];

        DB::transaction(function () use ($exam, $ids) {
            foreach ($ids as $position => $id) {
                $exam->sections()->where('id', $id)->update(['order' => $position]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function updateSection(ExamSection $section, Request $request): JsonResponse
    {
        $this->authorize('update', $section->exam);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
        ]);

        $section->update($data);

        return response()->json(['ok' => true]);
    }

    public function destroySection(ExamSection $section, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $section->exam);
        $section->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Section supprimée.');
    }

    // ---- Questions ----

    public function storeQuestion(ExamSection $section, StoreQuestionRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $section->exam);

        $question = DB::transaction(function () use ($section, $request) {
            return $section->questions()->create($this->buildQuestionAttributes($request));
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'data' => $this->sanitizeQuestion($question),
            ]);
        }

        return back()->with('success', 'Question ajoutée.');
    }

    public function updateQuestion(Question $question, StoreQuestionRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $question->section->exam);

        $question->update($this->buildQuestionAttributes($request));

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'data' => $this->sanitizeQuestion($question->fresh()),
            ]);
        }

        return back()->with('success', 'Question mise à jour.');
    }

    public function destroyQuestion(Question $question, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $question->section->exam);
        $question->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Question supprimée.');
    }

    private function buildQuestionAttributes(StoreQuestionRequest $request): array
    {
        $type = QuestionType::from($request->string('type'));

        $attrs = [
            'type' => $type->value,
            'prompt' => $request->string('prompt'),
            'points' => $request->float('points'),
            'bareme_text' => $request->string('bareme_text')->toString() ?: null,
            'order' => $request->integer('order') ?? 0,
            'choices' => null,
            'autograde_config' => null,
        ];

        switch ($type) {
            case QuestionType::VF:
                $attrs['autograde_config'] = [
                    'correct' => $request->string('correct')->toString(),
                    'penalty' => $request->filled('penalty') ? (float) $request->input('penalty') : 0,
                ];
                break;
            case QuestionType::QCM:
                $attrs['choices'] = array_values($request->input('choices', []));
                $attrs['autograde_config'] = [
                    'correct' => $request->string('correct')->toString(),
                ];
                break;
            case QuestionType::CODE:
                $attrs['autograde_config'] = ['language_hint' => $request->string('language_hint')->toString() ?: null];
                break;
            case QuestionType::ESSAY:
                $attrs['autograde_config'] = array_filter([
                    'min_words' => $request->filled('min_words') ? (int) $request->input('min_words') : null,
                    'max_words' => $request->filled('max_words') ? (int) $request->input('max_words') : null,
                ]);
                break;
            case QuestionType::FILE_UPLOAD:
                $attrs['autograde_config'] = [
                    'accepted_extensions' => $request->input('accepted_extensions', ['pdf']),
                    'max_size_mb' => (int) ($request->input('max_size_mb') ?: 5),
                ];
                break;
            case QuestionType::SHORT:
                $attrs['autograde_config'] = null;
                break;
        }

        return $attrs;
    }

    private function sanitizeQuestion(Question $question): array
    {
        // NEVER expose autograde_config to the client (would leak the right answer).
        return [
            'id' => $question->id,
            'type' => $question->type->value,
            'prompt' => $question->prompt,
            'points' => $question->points,
            'bareme_text' => $question->bareme_text,
            'order' => $question->order,
            'choices' => $question->choices,
        ];
    }
}
