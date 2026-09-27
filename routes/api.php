<?php

use App\Http\Controllers\Api\CheckController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/check', CheckController::class);
    Route::get('/stats', StatsController::class);
    Route::get('/recommendations', RecommendationController::class);
});
