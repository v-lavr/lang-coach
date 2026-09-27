<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    /** @param array<int, array{type: string, original: string, replacement: string, count: int}> $errors */
    public function generate(array $errors): array
    {
        if ($errors === []) {
            return [];
        }
        try {
            $response = Http::timeout((int) config('services.openai.timeout'))->acceptJson()->withToken((string) config('services.openai.key'))->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [['role' => 'system', 'content' => 'Return JSON only with a recommendations array. Each item has type, title, explanation, practice, and examples. Use only the aggregated error data.'], ['role' => 'user', 'content' => json_encode(['recurring_errors' => $errors], JSON_THROW_ON_ERROR)]],
            ]);
        } catch (ConnectionException|\JsonException $exception) {
            Log::warning('Recommendation service failed.', ['exception' => $exception->getMessage()]);
            throw new RecommendationException('Recommendations are temporarily unavailable.', previous: $exception);
        }
        if (! $response->successful()) {
            Log::warning('OpenAI returned a non-success status.', ['status' => $response->status()]);
            throw new RecommendationException('Recommendations are temporarily unavailable.');
        }
        try {
            $result = json_decode((string) data_get($response->json(), 'choices.0.message.content'), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            Log::warning('OpenAI returned invalid recommendation JSON.', [
                'has_message_content' => filled(data_get($response->json(), 'choices.0.message.content')),
            ]);

            throw new RecommendationException('Recommendations are temporarily unavailable.', previous: $exception);
        }
        if (! is_array($result) || ! is_array($result['recommendations'] ?? null)) {
            Log::warning('OpenAI recommendation JSON has an invalid structure.');

            throw new RecommendationException('Recommendations are temporarily unavailable.');
        }
        foreach ($result['recommendations'] as $recommendation) {
            if (! is_array($recommendation) || ! is_string($recommendation['type'] ?? null) || ! is_string($recommendation['title'] ?? null) || ! is_string($recommendation['explanation'] ?? null) || ! is_string($recommendation['practice'] ?? null) || ! is_array($recommendation['examples'] ?? null)) {
                Log::warning('OpenAI recommendation item has an invalid structure.');

                throw new RecommendationException('Recommendations are temporarily unavailable.');
            }
        }

        return $result['recommendations'];
    }
}
