<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\ApiKeyController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\PromptController;
use App\Http\Controllers\Web\PromptVersionController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Prompts
    Route::resource('prompts', PromptController::class);

    // Prompt versions
    Route::get('prompts/{prompt}/versions', [PromptVersionController::class, 'index'])
        ->name('prompts.versions.index');
    Route::get('prompts/{prompt}/versions/create', [PromptVersionController::class, 'create'])
        ->name('prompts.versions.create');
    Route::post('prompts/{prompt}/versions', [PromptVersionController::class, 'store'])
        ->name('prompts.versions.store');
    Route::get('prompts/{prompt}/versions/{version}', [PromptVersionController::class, 'show'])
        ->name('prompts.versions.show');
    Route::post('prompts/{prompt}/versions/{version}/activate', [PromptVersionController::class, 'activate'])
        ->name('prompts.versions.activate');

    // API keys
    Route::get('api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::get('api-keys/create', [ApiKeyController::class, 'create'])->name('api-keys.create');
    Route::post('api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    // Admin: users
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });
});

require __DIR__.'/auth.php';
