<?php

use App\Http\Controllers\Admin\ApiUsageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\TeacherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/ping', fn () => response('pong'))->name('ping');

    Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
    Route::post('/teachers/approve', [TeacherController::class, 'approve'])->name('teachers.approve');
    Route::post('/teachers/disable', [TeacherController::class, 'disable'])->name('teachers.disable');

    Route::get('/settings', [PlatformSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [PlatformSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test', [PlatformSettingsController::class, 'testCurrent'])->name('settings.test');

    Route::get('/usage', [ApiUsageController::class, 'index'])->name('usage.index');
});
