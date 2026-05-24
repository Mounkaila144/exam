<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiUsageLog;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApiUsageController extends Controller
{
    public function index(): View
    {
        $driver = DB::connection()->getDriverName();
        $monthExpr = match ($driver) {
            'pgsql' => "to_char(date_trunc('month', occurred_at), 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', occurred_at)",
            default => "DATE_FORMAT(occurred_at, '%Y-%m')",
        };

        $rows = ApiUsageLog::query()
            ->with('teacher:id,name,email')
            ->selectRaw("{$monthExpr} as month, teacher_id, COUNT(*) as calls, SUM(tokens_in) as tokens_in, SUM(tokens_out) as tokens_out, SUM(cost_cents) as cost_cents, SUM(CASE WHEN status <> 'ok' THEN 1 ELSE 0 END) as errors")
            ->groupByRaw("{$monthExpr}, teacher_id")
            ->orderByDesc('month')
            ->get();

        return view('admin.usage', ['rows' => $rows]);
    }
}
