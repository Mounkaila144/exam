<?php

namespace App\Http\Middleware;

use App\Models\ExamAssignment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveExamAssignment
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        abort_if(! $token, 404);

        $assignment = ExamAssignment::with(['exam.sections.questions'])
            ->where('access_token', $token)
            ->first();

        abort_if(! $assignment, 404, 'Lien invalide.');

        $request->attributes->set('examAssignment', $assignment);

        return $next($request);
    }
}
