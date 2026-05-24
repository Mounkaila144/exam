<?php

namespace App\Http\Requests\Teacher;

use App\Domain\Exam\QuestionType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() && $this->user()?->isActive();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(QuestionType::class)],
            'prompt' => ['required', 'string'],
            'points' => ['required', 'numeric', 'min:0'],
            'bareme_text' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],

            // VF / QCM specific
            'correct' => ['nullable', 'string'],
            'penalty' => ['nullable', 'numeric'],
            'choices' => ['nullable', 'array', 'min:2', 'max:6'],
            'choices.*.key' => ['required_with:choices', 'string', 'max:5'],
            'choices.*.label' => ['required_with:choices', 'string'],

            // Code
            'language_hint' => ['nullable', 'string', 'max:50'],

            // Essay
            'min_words' => ['nullable', 'integer', 'min:0'],
            'max_words' => ['nullable', 'integer', 'min:0'],

            // File
            'accepted_extensions' => ['nullable', 'array'],
            'accepted_extensions.*' => ['string', 'max:10'],
            'max_size_mb' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $type = QuestionType::tryFrom($this->input('type'));
            if ($type === QuestionType::QCM) {
                if (! $this->filled('correct') || ! is_array($this->input('choices'))) {
                    $v->errors()->add('correct', 'Le QCM doit indiquer la bonne réponse.');
                }
            }
            if ($type === QuestionType::VF && ! in_array($this->input('correct'), ['VRAI', 'FAUX'], true)) {
                $v->errors()->add('correct', 'Indiquez VRAI ou FAUX.');
            }
        });
    }
}
