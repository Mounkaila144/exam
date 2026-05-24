<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class ImportGradesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() && $this->user()?->isActive();
    }

    public function rules(): array
    {
        return [
            'grades_json' => ['required', 'string'],
        ];
    }
}
