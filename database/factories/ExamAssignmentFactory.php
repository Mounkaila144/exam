<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamAssignment>
 */
class ExamAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'student_name' => fake()->name(),
            'student_email' => fake()->unique()->safeEmail(),
            'student_matricule' => fake()->numerify('20#####'),
            'access_token' => Str::random(48),
        ];
    }
}
