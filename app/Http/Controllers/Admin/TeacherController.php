<?php

namespace App\Http\Controllers\Admin;

use App\Domain\User\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveTeacherRequest;
use App\Http\Requests\Admin\DisableTeacherRequest;
use App\Mail\TeacherApprovedMailable;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $teachers = User::teachers()
            ->when(in_array($status, ['pending', 'active', 'disabled'], true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.teachers', [
            'teachers' => $teachers,
            'activeStatus' => $status,
        ]);
    }

    public function approve(ApproveTeacherRequest $request): RedirectResponse
    {
        $teacher = User::findOrFail($request->integer('teacher_id'));

        $teacher->update(['status' => UserStatus::ACTIVE->value]);

        Mail::to($teacher->email)->queue(new TeacherApprovedMailable($teacher));

        Log::info('admin.teacher.activated', [
            'actor_id' => $request->user()->id,
            'target_id' => $teacher->id,
        ]);

        return back()->with('success', "Le compte de {$teacher->name} a été activé.");
    }

    public function disable(DisableTeacherRequest $request): RedirectResponse
    {
        $teacher = User::findOrFail($request->integer('teacher_id'));

        $teacher->update([
            'status' => UserStatus::DISABLED->value,
            'remember_token' => null,
        ]);

        Log::info('admin.teacher.disabled', [
            'actor_id' => $request->user()->id,
            'target_id' => $teacher->id,
        ]);

        return back()->with('success', "Le compte de {$teacher->name} a été désactivé.");
    }
}
