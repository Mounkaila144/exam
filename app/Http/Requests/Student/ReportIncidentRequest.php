<?php

namespace App\Http\Requests\Student;

use App\Domain\Incident\IncidentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(IncidentType::class)],
            'payload' => ['nullable', 'array'],
        ];
    }
}
