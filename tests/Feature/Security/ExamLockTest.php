<?php

namespace Tests\Feature\Security;

use App\Domain\Incident\IncidentType;
use App\Events\StudentLocked;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\User;
use App\Services\Security\ExamLockService;
use App\Services\Security\IncidentRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ExamLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_critical_incident_locks_the_assignment(): void
    {
        Event::fake([StudentLocked::class]);

        $teacher = User::factory()->create();
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = ExamAssignment::factory()->create(['exam_id' => $exam->id]);

        $recorder = app(IncidentRecorder::class);
        $recorder->record($assignment, IncidentType::TAB_BLUR, ['duration_ms' => 1000]);

        $this->assertTrue($assignment->fresh()->locked);
        $this->assertSame('tab_blur', $assignment->fresh()->locked_reason);
        Event::assertDispatched(StudentLocked::class);
    }

    public function test_idempotent_lock_does_not_rebroadcast(): void
    {
        Event::fake([StudentLocked::class]);

        $assignment = ExamAssignment::factory()->create(['locked' => true, 'locked_reason' => 'tab_blur', 'locked_at' => now()]);

        $lock = app(ExamLockService::class);
        $result = $lock->lock($assignment, 'fullscreen_exit');

        $this->assertFalse($result);
        Event::assertNotDispatched(StudentLocked::class);
    }

    public function test_unlock_clears_state_and_journals_incident(): void
    {
        $teacher = User::factory()->create();
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'locked' => true,
            'locked_reason' => 'tab_blur',
            'locked_at' => now(),
        ]);

        $lock = app(ExamLockService::class);
        $lock->unlock($assignment, $teacher, 'test');

        $this->assertFalse($assignment->fresh()->locked);
        $this->assertSame(1, $assignment->incidents()->where('type', 'unlocked_by_teacher')->count());
    }
}
