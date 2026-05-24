<?php

namespace App\Services\Grading;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ClaudeApiClient
{
    public function __construct(private readonly ?Client $client = null)
    {
    }

    /**
     * Lightweight liveness/credential check used by the admin settings page.
     */
    public function ping(string $apiKey, string $model): void
    {
        $payload = [
            'model' => $model,
            'max_tokens' => 16,
            'messages' => [[
                'role' => 'user',
                'content' => 'ping',
            ]],
        ];

        $this->call($apiKey, $payload, timeoutSeconds: 30);
    }

    /**
     * Send a full grading request. Returns the raw API response array.
     *
     * @return array{tokens_in:int,tokens_out:int,content:string,model:string}
     */
    public function send(string $apiKey, string $model, string $prompt, int $maxTokens = 8000): array
    {
        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => [[
                'role' => 'user',
                'content' => $prompt,
            ]],
        ];

        $response = $this->call($apiKey, $payload, timeoutSeconds: config('claude.timeout_seconds', 180));

        $content = '';
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                $content .= $block['text'];
            }
        }

        return [
            'tokens_in' => (int) ($response['usage']['input_tokens'] ?? 0),
            'tokens_out' => (int) ($response['usage']['output_tokens'] ?? 0),
            'content' => $content,
            'model' => $response['model'] ?? $model,
        ];
    }

    private function call(string $apiKey, array $payload, int $timeoutSeconds): array
    {
        $client = $this->client ?? new Client([
            'base_uri' => rtrim(config('claude.base_url'), '/').'/',
            'timeout' => $timeoutSeconds,
        ]);

        try {
            $response = $client->post('v1/messages', [
                'headers' => [
                    'x-api-key' => $apiKey,
                    'anthropic-version' => config('claude.api_version'),
                    'content-type' => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (GuzzleException $e) {
            Log::error('claude.api_error', ['exception' => $e->getMessage()]);
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Réponse Anthropic invalide.');
        }

        return $decoded;
    }
}
