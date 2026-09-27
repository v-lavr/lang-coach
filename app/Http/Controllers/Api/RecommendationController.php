<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CorrectionError;
use App\Services\RecommendationException;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, RecommendationService $recommendationService): JsonResponse
    {
        $errors = CorrectionError::query()->whereHas('correction', fn ($query) => $query->where('user_id', $request->user()->id))
            ->select('type', 'original', 'replacement', DB::raw('count(*) as count'))->groupBy('type', 'original', 'replacement')->orderByDesc('count')->limit(10)->get()->map(fn ($error) => $error->only(['type', 'original', 'replacement', 'count']))->all();
        try {
            return response()->json(['recommendations' => $recommendationService->generate($errors)]);
        } catch (RecommendationException $exception) {
            return response()->json(['message' => 'Recommendations are temporarily unavailable.'], 502);
        }
    }
}
