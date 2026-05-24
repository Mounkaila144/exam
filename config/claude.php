<?php

return [
    'base_url' => env('ANTHROPIC_API_BASE', 'https://api.anthropic.com'),
    'default_model' => env('CLAUDE_DEFAULT_MODEL', 'claude-opus-4-7'),
    'timeout_seconds' => (int) env('CLAUDE_TIMEOUT_SECONDS', 180),
    'api_version' => '2023-06-01',

    'pricing_per_million_tokens' => [
        'claude-opus-4-7' => ['input' => 1500, 'output' => 7500],
        'claude-sonnet-4-6' => ['input' => 300, 'output' => 1500],
        'claude-haiku-4-5-20251001' => ['input' => 100, 'output' => 400],
    ],
];
