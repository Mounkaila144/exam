<?php

use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsureTeacherIsActive;
use App\Http\Middleware\ExamIsLive;
use App\Http\Middleware\ResolveExamAssignment;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/student/*',
        ]);

        $middleware->alias([
            'admin' => EnsureAdminRole::class,
            'teacher.active' => EnsureTeacherIsActive::class,
            'exam.assignment' => ResolveExamAssignment::class,
            'exam.live' => ExamIsLive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
