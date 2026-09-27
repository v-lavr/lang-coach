<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckSentenceRequest;
use App\Models\Correction;
use App\Services\GrammarCorrectionException;
use App\Services\GrammarCorrectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CheckController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(CheckSentenceRequest $request, GrammarCorrectionService $grammarCorrectionService): JsonResponse
    {
        try {
            $result = $grammarCorrectionService->check($request->string('text')->toString());
        } catch (GrammarCorrectionException $exception) {
            return response()->json(['message' => 'Grammar checking is temporarily unavailable.'], 502);
        }

        if (! $result['changed']) {
            return response()->json($result);
        }

        DB::transaction(function () use ($request, $result): void {
            /** @var Correction $correction */
            $correction = $request->user()->corrections()->create([
                'original_text' => $result['original'],
                'corrected_text' => $result['corrected'],
            ]);

            $correction->errors()->createMany($result['errors']);
        });

        return response()->json($result);
    }
}
