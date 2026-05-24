<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Services\Exam\AssignmentTokenGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamStudentController extends Controller
{
    public function __construct(private readonly AssignmentTokenGenerator $tokens)
    {
    }

    public function index(Exam $exam): View
    {
        $this->authorize('update', $exam);

        $assignments = $exam->assignments()->orderBy('student_name')->paginate(50);

        return view('teacher.exams.students', compact('exam', 'assignments'));
    }

    public function store(Exam $exam, Request $request): RedirectResponse
    {
        $this->authorize('update', $exam);

        $data = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'student_email' => ['required', 'email', 'max:255'],
            'student_matricule' => ['nullable', 'string', 'max:100'],
            'student_group' => ['nullable', 'string', 'max:100'],
        ]);

        if ($exam->assignments()->where('student_email', $data['student_email'])->exists()) {
            return back()->withErrors(['student_email' => 'Cet étudiant est déjà inscrit à l\'examen.']);
        }

        $exam->assignments()->create([
            ...$data,
            'access_token' => $this->tokens->makeToken(),
        ]);

        return back()->with('success', 'Étudiant ajouté.');
    }

    public function destroy(ExamAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment->exam);

        abort_if($assignment->opened_at !== null, 422, 'Cet étudiant a déjà commencé l\'examen.');

        $exam = $assignment->exam;
        $assignment->delete();

        return redirect()->route('teacher.exams.students.index', $exam)->with('success', 'Étudiant retiré.');
    }
}
