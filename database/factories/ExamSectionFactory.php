<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamSection>
 */
class ExamSectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'order' => 0,
            'title' => 'Section '.fake()->randomNumber(2),
        ];
    }
}
