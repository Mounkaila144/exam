<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin dashboard (protected)
Route::middleware('auth')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/submissions/{submission}', [AdminController::class, 'show'])->name('admin.show');
    Route::post('/export-claude', [AdminController::class, 'exportForClaude'])->name('admin.export');
    Route::post('/import-grades', [AdminController::class, 'importGrades'])->name('admin.import');
    Route::post('/submissions/{submission}/send-grade', [AdminController::class, 'sendGrade'])->name('admin.sendGrade');
    Route::post('/send-all-grades', [AdminController::class, 'sendAllGrades'])->name('admin.sendAllGrades');
});

// API endpoint for exam HTML form (public)
Route::post('/api/submit-exam', [SubmissionController::class, 'store'])->name('api.submit');
