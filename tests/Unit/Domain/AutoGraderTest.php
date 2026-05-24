<?php

namespace Tests\Unit\Domain;

use App\Domain\Exam\AutoGrader;
use PHPUnit\Framework\TestCase;

class AutoGraderTest extends TestCase
{
    public function test_vf_correct_returns_full_points(): void
    {
        $score = AutoGrader::gradeVf('VRAI', ['correct' => 'VRAI', 'penalty' => -0.5], 2.0);
        $this->assertSame(2.0, $score);
    }

    public function test_vf_incorrect_returns_penalty(): void
    {
        $score = AutoGrader::gradeVf('FAUX', ['correct' => 'VRAI', 'penalty' => -0.5], 2.0);
        $this->assertSame(-0.5, $score);
    }

    public function test_vf_no_answer_returns_zero(): void
    {
        $this->assertSame(0.0, AutoGrader::gradeVf(null, ['correct' => 'VRAI', 'penalty' => -0.5], 2.0));
        $this->assertSame(0.0, AutoGrader::gradeVf('', ['correct' => 'VRAI', 'penalty' => -0.5], 2.0));
    }

    public function test_qcm_correct_returns_full_points(): void
    {
        $this->assertSame(3.0, AutoGrader::gradeQcm('B', ['correct' => 'B'], 3.0));
    }

    public function test_qcm_incorrect_returns_zero(): void
    {
        $this->assertSame(0.0, AutoGrader::gradeQcm('A', ['correct' => 'B'], 3.0));
    }

    public function test_qcm_no_answer_returns_zero(): void
    {
        $this->assertSame(0.0, AutoGrader::gradeQcm(null, ['correct' => 'B'], 3.0));
    }
}
