<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\Exam\ExamPublisherService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class ExamPublishController extends Controller
{
    public function __construct(private readonly ExamPublisherService $publisher)
    {
    }

    public function store(Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        try {
            $this->publisher->publish($exam);
        } catch (RuntimeException $e) {
            return back()->withErrors(['publish' => $e->getMessage()]);
        }

        return back()->with('success', 'Examen publié, liens envoyés aux étudiants.');
    }
}
