<?php

namespace App\Http\Controllers\Auth;

use App\Domain\User\UserRole;
use App\Domain\User\UserStatus;
use App\Http\Controllers\Controller;
use App\Mail\NewTeacherSignedUpMailable;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $teacher = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::TEACHER->value,
            'status' => UserStatus::PENDING->value,
        ]);

        $admin = User::admins()->first();
        if ($admin) {
            Mail::to($admin->email)->queue(new NewTeacherSignedUpMailable($teacher));
        }

        return redirect()->route('register.pending');
    }

    public function pending(): View
    {
        return view('auth.pending');
    }
}
