<?php

namespace App\Http\Controllers\Admin;

use App\Domain\User\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\ApiUsageLog;
use App\Models\Exam;
use App\Models\Incident;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        $teachersByStatus = User::teachers()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $teachersWidget = [
            'pending' => $teachersByStatus[UserStatus::PENDING->value] ?? 0,
            'active' => $teachersByStatus[UserStatus::ACTIVE->value] ?? 0,
            'disabled' => $teachersByStatus[UserStatus::DISABLED->value] ?? 0,
        ];

        $examsPublishedThisMonth = Exam::where('status', 'published')
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $apiUsage = ApiUsageLog::where('occurred_at', '>=', $startOfMonth)
            ->selectRaw('COALESCE(SUM(tokens_in),0) as tokens_in, COALESCE(SUM(tokens_out),0) as tokens_out, COALESCE(SUM(cost_cents),0) as cost_cents')
            ->first();

        $incidentsThisMonth = Incident::where('occurred_at', '>=', $startOfMonth)->count();

        return view('admin.dashboard', [
            'teachersWidget' => $teachersWidget,
            'examsPublishedThisMonth' => $examsPublishedThisMonth,
            'apiUsage' => $apiUsage,
            'incidentsThisMonth' => $incidentsThisMonth,
        ]);
    }
}
