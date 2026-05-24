<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $exams = $request->user()
            ->exams()
            ->withCount('assignments')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('teacher.dashboard', ['exams' => $exams]);
    }
}
