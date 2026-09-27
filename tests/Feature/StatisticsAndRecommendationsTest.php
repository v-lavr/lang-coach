<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function correctionWithError(User $user, string $type, string $original, string $replacement): void
{
    $user->corrections()->create(['original_text' => $original, 'corrected_text' => $replacement])->errors()->create(['type' => $type, 'original' => $original, 'replacement' => $replacement, 'explanation' => null]);
}

test('stats require authentication and isolate user data', function (): void {
    $this->getJson('/api/stats')->assertUnauthorized();
    $user = User::factory()->create();
    $other = User::factory()->create();
    correctionWithError($user, 'verb_form', 'knew', 'know');
    correctionWithError($user, 'verb_form', 'knew', 'know');
    correctionWithError($other, 'preposition', 'in Monday', 'on Monday');
    $this->actingAs($user, 'sanctum')->getJson('/api/stats')->assertOk()->assertJsonPath('top_error_categories.0.type', 'verb_form')->assertJsonPath('top_error_categories.0.count', 2)->assertJsonPath('frequent_mistakes.0.count', 2);
});

test('recommendations use only aggregated current user errors', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    correctionWithError($user, 'preposition', 'in Monday', 'on Monday');
    correctionWithError($other, 'verb_form', 'knew', 'know');
    Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode(['recommendations' => [['type' => 'prepositions', 'title' => 'Days', 'explanation' => 'Use on.', 'practice' => 'Practice dates.', 'examples' => ['on Monday']]]])]]]])]);
    $this->actingAs($user, 'sanctum')->getJson('/api/recommendations')->assertOk()->assertJsonPath('recommendations.0.type', 'prepositions');
    Http::assertSent(fn ($request) => str_contains($request->data()['messages'][1]['content'], 'in Monday') && ! str_contains($request->data()['messages'][1]['content'], 'knew'));
});

test('recommendations require authentication and fail safely', function (): void {
    $this->getJson('/api/recommendations')->assertUnauthorized();
    $user = User::factory()->create();
    correctionWithError($user, 'verb_form', 'knew', 'know');
    Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response([], 500)]);
    $this->actingAs($user, 'sanctum')->getJson('/api/recommendations')->assertStatus(502);
});

test('recommendations fail safely for invalid OpenAI output', function (): void {
    $user = User::factory()->create();
    correctionWithError($user, 'verb_form', 'knew', 'know');
    Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'not json']]]])]);

    $this->actingAs($user, 'sanctum')->getJson('/api/recommendations')->assertStatus(502);
});
