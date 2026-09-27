<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['services.grammar.url' => 'http://grammar.test/api/check']);
});

test('check requires authentication', function (): void {
    $this->postJson('/api/check', ['text' => "I didn't knew about this."])->assertUnauthorized();
});

test('it validates a sentence', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/check', [])->assertUnprocessable()->assertJsonValidationErrors('text');
});

test('it persists a changed grammar response and errors', function (): void {
    Http::fake(['http://grammar.test/api/check' => Http::response([
        'corrected' => "I didn't know about this.",
        'errors' => [['type' => 'verb_form', 'original' => 'knew', 'replacement' => 'know', 'explanation' => "Use the base verb after did/didn't."]],
    ])]);
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/check', ['text' => "I didn't knew about this."])
        ->assertOk()->assertJsonPath('changed', true)->assertJsonPath('corrected', "I didn't know about this.");

    $this->assertDatabaseHas('corrections', ['user_id' => $user->id, 'original_text' => "I didn't knew about this."]);
    $this->assertDatabaseHas('errors', ['type' => 'verb_form', 'original' => 'knew', 'replacement' => 'know']);
});

test('it does not persist unchanged grammar responses', function (): void {
    Http::fake(['http://grammar.test/api/check' => Http::response([
        'corrected' => 'This sentence is correct.',
        'errors' => [],
    ])]);
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/check', ['text' => 'This sentence is correct.'])->assertOk()->assertJsonPath('changed', false);

    $this->assertDatabaseCount('corrections', 0);
});

test('it returns a safe error when the grammar service fails', function (): void {
    Http::fake(['http://grammar.test/api/check' => Http::response([], 500)]);
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/check', ['text' => 'This should fail.'])
        ->assertStatus(502)->assertJson(['message' => 'Grammar checking is temporarily unavailable.']);
});
