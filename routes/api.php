<?php

use App\Http\Controllers\Api\PromptController;
use App\Http\Controllers\Api\VersionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.key'])->group(function () {
    Route::get('prompts', [PromptController::class, 'index']);
    Route::get('prompts/{slug}', [PromptController::class, 'show']);
    Route::get('prompts/{slug}/versions', [VersionController::class, 'index']);
    Route::get('prompts/{slug}/versions/{version}', [VersionController::class, 'show']);
    Route::post('prompts/{slug}/render', [PromptController::class, 'render']);
});
