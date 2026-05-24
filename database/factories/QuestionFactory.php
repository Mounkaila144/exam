<?php

namespace Database\Factories;

use App\Domain\Exam\QuestionType;
use App\Models\ExamSection;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_section_id' => ExamSection::factory(),
            'order' => 0,
            'type' => QuestionType::SHORT->value,
            'prompt' => fake()->sentence().' ?',
            'points' => 1,
        ];
    }

    public function vf(string $correct = 'VRAI'): static
    {
        return $this->state(fn () => [
            'type' => QuestionType::VF->value,
            'autograde_config' => ['correct' => $correct, 'penalty' => 0],
        ]);
    }

    public function qcm(string $correct = 'A'): static
    {
        return $this->state(fn () => [
            'type' => QuestionType::QCM->value,
            'choices' => [['key' => 'A', 'label' => 'A'], ['key' => 'B', 'label' => 'B']],
            'autograde_config' => ['correct' => $correct],
        ]);
    }
}
