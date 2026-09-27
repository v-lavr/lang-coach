<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CorrectionError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $errors = CorrectionError::query()->whereHas('correction', fn ($query) => $query->where('user_id', $userId));

        return response()->json([
            'corrections_today' => $request->user()->corrections()->where('created_at', '>=', Carbon::today())->count(),
            'corrections_this_week' => $request->user()->corrections()->where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
            'top_error_categories' => (clone $errors)->select('type', DB::raw('count(*) as count'))->groupBy('type')->orderByDesc('count')->limit(5)->get(),
            'frequent_mistakes' => (clone $errors)->select('original', 'replacement', DB::raw('count(*) as count'))->groupBy('original', 'replacement')->orderByDesc('count')->limit(5)->get(),
        ]);
    }
}
