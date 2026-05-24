<?php

use App\Http\Controllers\Student\ExamAnswerController;
use App\Http\Controllers\Student\ExamEntryController;
use App\Http\Controllers\Student\ExamHeartbeatController;
use App\Http\Controllers\Student\ExamRuntimeController;
use App\Http\Controllers\Student\ExamSubmitController;
use App\Http\Controllers\Student\IncidentReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['signed', 'exam.assignment'])->group(function () {
    Route::get('/exam/{token}', [ExamEntryController::class, 'show'])
        ->middleware('exam.live')
        ->name('student.exam.show');
    Route::post('/exam/{token}/start', [ExamEntryController::class, 'start'])
        ->middleware('exam.live')
        ->name('student.exam.start');

    Route::get('/exam/{token}/run', [ExamRuntimeController::class, 'show'])
        ->middleware('exam.live')
        ->name('student.exam.run');

    Route::post('/exam/{token}/submit', [ExamSubmitController::class, 'store'])
        ->middleware('exam.live')
        ->name('student.exam.submit');
});

// "Merci, copie envoyée" page — no signed URL required (token in URL is sufficient,
// the assignment is already submitted so nothing sensitive remains).
Route::middleware(['exam.assignment'])
    ->get('/exam/{token}/submitted', [ExamSubmitController::class, 'submitted'])
    ->name('student.exam.submitted');

// AJAX endpoints authenticated by the 48-char access_token in the URL (no signed-URL needed here
// because regenerating a signature client-side per request would defeat the JS runtime).
Route::middleware(['exam.assignment'])
    ->prefix('api/student/{token}')
    ->group(function () {
        Route::get('/heartbeat', [ExamHeartbeatController::class, 'show'])->name('student.api.heartbeat');
        Route::post('/answers', [ExamAnswerController::class, 'store'])->name('student.api.answers');
        Route::post('/incidents', [IncidentReportController::class, 'store'])->name('student.api.incidents');
    });
