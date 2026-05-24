<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        abort_unless($user->isTeacher(), 403, 'Accès professeur requis.');
        abort_unless($user->isActive(), 403, 'Votre compte n\'est pas encore actif.');

        return $next($request);
    }
}
