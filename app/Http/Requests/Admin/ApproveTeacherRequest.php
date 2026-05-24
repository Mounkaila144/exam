<?php

namespace App\Http\Requests\Admin;

use App\Domain\User\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', User::class);
    }

    public function rules(): array
    {
        return [
            'teacher_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', UserRole::TEACHER->value),
            ],
        ];
    }
}
