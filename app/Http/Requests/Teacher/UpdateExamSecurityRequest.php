<?php

namespace App\Http\Requests\Teacher;

use App\Domain\Exam\ExamStatus;
use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExamSecurityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam
            && $this->user()->can('update', $exam)
            && $exam->status === ExamStatus::DRAFT;
    }

    public function rules(): array
    {
        return [
            'enforce_fullscreen' => ['boolean'],
            'lock_on_first_offense' => ['boolean'],
            'lock_on_offense_count' => ['nullable', 'integer', 'min:1', 'max:99'],
            'block_copy_paste' => ['boolean'],
            'block_right_click' => ['boolean'],
            'block_devtools_shortcuts' => ['boolean'],
            'detect_devtools_open' => ['boolean'],
            'lock_on_ip_change' => ['boolean'],
        ];
    }
}
