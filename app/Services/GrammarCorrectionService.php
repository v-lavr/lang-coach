<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GrammarCorrectionService
{
    /**
     * @return array{changed: bool, original: string, corrected: string, errors: array<int, array{type: string, original: string, replacement: string, explanation: ?string}>}
     */
    public function check(string $text): array
    {
        try {
            $response = Http::timeout((int) config('services.grammar.timeout'))
                ->acceptJson()
                ->post((string) config('services.grammar.url'), ['text' => $text]);
        } catch (ConnectionException $exception) {
            Log::warning('Grammar model connection failed.', ['exception' => $exception->getMessage()]);

            throw new GrammarCorrectionException('The grammar service is unavailable.', previous: $exception);
        }

        if (! $response->successful()) {
            Log::warning('Grammar model returned a non-success status.', ['status' => $response->status()]);

            throw new GrammarCorrectionException('The grammar service is unavailable.');
        }

        $result = $response->json();

        if (! is_array($result) || ! is_string($result['corrected'] ?? null) || ! is_array($result['errors'] ?? null)) {
            Log::warning('Grammar model returned an invalid response.');

            throw new GrammarCorrectionException('The grammar service returned an invalid response.');
        }

        foreach ($result['errors'] as $error) {
            if (! is_array($error) || ! is_string($error['type'] ?? null) || ! is_string($error['original'] ?? null) || ! is_string($error['replacement'] ?? null) || (! is_string($error['explanation'] ?? null) && ! is_null($error['explanation'] ?? null))) {
                throw new GrammarCorrectionException('The grammar service returned an invalid response.');
            }
        }

        return [
            'changed' => $result['corrected'] !== $text,
            'original' => $text,
            'corrected' => $result['corrected'],
            'errors' => $result['errors'],
        ];
    }
}
