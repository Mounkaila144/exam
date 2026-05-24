<?php

namespace Tests\Feature\Security;

use App\Models\ExamAssignment;
use App\Services\Exam\AssignmentTokenGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_token_is_accepted_and_tampered_token_is_rejected(): void
    {
        $assignment = ExamAssignment::factory()->create();
        $url = app(AssignmentTokenGenerator::class)->signedUrlFor($assignment);

        $this->get($url)->assertStatus(200);

        $tampered = $url.'tampered';
        $this->get($tampered)->assertStatus(403);
    }
}
