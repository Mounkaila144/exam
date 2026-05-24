<?php

use App\Http\Controllers\Teacher\AssignmentController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\ExamBuilderController;
use App\Http\Controllers\Teacher\ExamMonitorController;
use App\Http\Controllers\Teacher\ExamPublishController;
use App\Http\Controllers\Teacher\ExamStudentController;
use App\Http\Controllers\Teacher\GradingController;
use App\Http\Controllers\Teacher\PushSubscriptionController;
use App\Http\Controllers\Teacher\StudentImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'teacher.active'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Exam CRUD
    Route::get('/exams', [ExamBuilderController::class, 'index'])->name('exams.index');
    Route::get('/exams/create', [ExamBuilderController::class, 'create'])->name('exams.create');
    Route::post('/exams', [ExamBuilderController::class, 'store'])->name('exams.store');
    Route::get('/exams/{exam}/edit', [ExamBuilderController::class, 'edit'])->name('exams.edit');
    Route::put('/exams/{exam}', [ExamBuilderController::class, 'update'])->name('exams.update');
    Route::delete('/exams/{exam}', [ExamBuilderController::class, 'destroy'])->name('exams.destroy');
    Route::patch('/exams/{exam}/security', [ExamBuilderController::class, 'updateSecurity'])->name('exams.security.update');

    // Sections + questions (AJAX)
    Route::post('/exams/{exam}/sections', [ExamBuilderController::class, 'storeSection'])->name('exams.sections.store');
    Route::patch('/exams/{exam}/sections/reorder', [ExamBuilderController::class, 'reorderSections'])->name('exams.sections.reorder');
    Route::patch('/sections/{section}', [ExamBuilderController::class, 'updateSection'])->name('sections.update');
    Route::delete('/sections/{section}', [ExamBuilderController::class, 'destroySection'])->name('sections.destroy');

    Route::post('/sections/{section}/questions', [ExamBuilderController::class, 'storeQuestion'])->name('sections.questions.store');
    Route::patch('/questions/{question}', [ExamBuilderController::class, 'updateQuestion'])->name('questions.update');
    Route::delete('/questions/{question}', [ExamBuilderController::class, 'destroyQuestion'])->name('questions.destroy');

    // Students
    Route::get('/exams/{exam}/students', [ExamStudentController::class, 'index'])->name('exams.students.index');
    Route::post('/exams/{exam}/students', [ExamStudentController::class, 'store'])->name('exams.students.store');
    Route::delete('/students/{assignment}', [ExamStudentController::class, 'destroy'])->name('students.destroy');
    Route::post('/exams/{exam}/students/import', [StudentImportController::class, 'store'])->name('exams.students.import');

    // Publication
    Route::post('/exams/{exam}/publish', [ExamPublishController::class, 'store'])->name('exams.publish');

    // Live monitor
    Route::get('/exams/{exam}/monitor', [ExamMonitorController::class, 'show'])->name('exams.monitor');
    Route::post('/assignments/{assignment}/unlock', [AssignmentController::class, 'unlock'])->name('assignments.unlock');

    // Grading
    Route::get('/exams/{exam}/grading', [GradingController::class, 'show'])->name('exams.grading');
    Route::post('/exams/{exam}/grading/export-claude', [GradingController::class, 'exportForClaude'])->name('exams.grading.export');
    Route::post('/exams/{exam}/grading/import', [GradingController::class, 'importGrades'])->name('exams.grading.import');
    Route::post('/exams/{exam}/grading/dispatch-api', [GradingController::class, 'dispatchClaudeApi'])->name('exams.grading.api');
    Route::post('/submissions/{submission}/send-grade', [GradingController::class, 'sendGrade'])->name('submissions.send-grade');
    Route::post('/exams/{exam}/grading/send-all', [GradingController::class, 'sendAllGrades'])->name('exams.grading.send-all');

    // Push subscriptions
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
});
