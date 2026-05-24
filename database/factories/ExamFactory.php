<?php

namespace Database\Factories;

use App\Domain\Exam\ExamStatus;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory(),
            'title' => 'Examen '.fake()->words(3, true),
            'description' => fake()->sentence(),
            'duration_minutes' => 60,
            'status' => ExamStatus::DRAFT->value,
            'security_settings' => Exam::DEFAULT_SECURITY_SETTINGS,
        ];
    }
}
