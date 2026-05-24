<?php

namespace App\Jobs;

use App\Models\ApiUsageLog;
use App\Models\Exam;
use App\Models\User;
use App\Notifications\GradingCompletedNotification;
use App\Notifications\GradingFailedNotification;
use App\Services\Grading\ClaudeApiClient;
use App\Services\Grading\ClaudeExportFormatter;
use App\Services\Grading\GradeImportService;
use App\Services\Security\PlatformApiKeyVault;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchClaudeGradingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(public int $examId, public int $teacherId)
    {
    }

    public function handle(
        PlatformApiKeyVault $vault,
        ClaudeExportFormatter $formatter,
        ClaudeApiClient $client,
        GradeImportService $importer,
    ): void {
        $exam = Exam::with('sections.questions', 'assignments.submission')->findOrFail($this->examId);
        $teacher = User::findOrFail($this->teacherId);

        $apiKey = $vault->getDecryptedKey();
        if (! $apiKey) {
            throw new \RuntimeException('Aucune clé API Claude configurée.');
        }

        $model = $vault->getModel();
        $submissions = $exam->assignments->filter(fn ($a) => $a->submission && $a->submission->status->value !== 'in_progress')->map->submission;

        if ($submissions->isEmpty()) {
            return;
        }

        $prompt = $formatter->format($exam, $submissions);

        $response = $client->send($apiKey, $model, $prompt);

        $jsonContent = $this->extractJson($response['content']);

        $result = $importer->importFromJson($exam, $jsonContent);

        $costCents = $this->estimateCostCents($model, $response['tokens_in'], $response['tokens_out']);

        ApiUsageLog::create([
            'teacher_id' => $teacher->id,
            'exam_id' => $exam->id,
            'provider' => 'anthropic',
            'model' => $response['model'],
            'tokens_in' => $response['tokens_in'],
            'tokens_out' => $response['tokens_out'],
            'cost_cents' => $costCents,
            'status' => 'ok',
            'occurred_at' => now(),
            'created_at' => now(),
        ]);

        $teacher->notify(new GradingCompletedNotification($exam, $result['updated'], $costCents));
    }

    public function failed(Throwable $e): void
    {
        Log::error('claude.grading.failed', [
            'exam_id' => $this->examId,
            'exception' => $e->getMessage(),
        ]);

        $teacher = User::find($this->teacherId);
        if ($teacher) {
            $teacher->notify(new GradingFailedNotification($this->examId, $e->getMessage()));
        }

        ApiUsageLog::create([
            'teacher_id' => $this->teacherId,
            'exam_id' => $this->examId,
            'provider' => 'anthropic',
            'model' => 'unknown',
            'tokens_in' => 0,
            'tokens_out' => 0,
            'cost_cents' => 0,
            'status' => 'error',
            'occurred_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function extractJson(string $content): string
    {
        if (preg_match('/```json\s*(.+?)```/s', $content, $m)) {
            return trim($m[1]);
        }

        return trim($content);
    }

    public static function estimateCostCents(string $model, int $tokensIn, int $tokensOut): int
    {
        $pricing = config("claude.pricing_per_million_tokens.{$model}");
        if (! $pricing) {
            // Fallback Opus-grade pricing.
            $pricing = ['input' => 1500, 'output' => 7500];
        }

        return (int) round(($tokensIn * $pricing['input'] + $tokensOut * $pricing['output']) / 1_000_000);
    }
}
