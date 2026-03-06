<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\Admin\LlmProviderController;
use App\Http\Controllers\Web\ApiKeyController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\LibraryController;
use App\Http\Controllers\Web\StoryController;
use App\Http\Controllers\Web\StoryStepController;
use App\Http\Controllers\Web\LlmResponseController;
use App\Http\Controllers\Web\PromptController;
use App\Http\Controllers\Web\PromptRunController;
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
    Route::resource('prompts', PromptController::class)->except(['show']);
    Route::get('prompts/{prompt}', [PromptController::class, 'show'])
        ->withTrashed()
        ->name('prompts.show');

    // Prompt soft-delete actions (admin only, resolve trashed models)
    Route::post('prompts/{prompt}/restore', [PromptController::class, 'restore'])
        ->middleware('role:admin')
        ->withTrashed()
        ->name('prompts.restore');
    Route::delete('prompts/{prompt}/force-delete', [PromptController::class, 'forceDelete'])
        ->middleware('role:admin')
        ->withTrashed()
        ->name('prompts.force-delete');

    // Prompt versions
    Route::get('prompts/{prompt}/versions', [PromptVersionController::class, 'index'])
        ->name('prompts.versions.index');
    Route::get('prompts/{prompt}/versions/create', [PromptVersionController::class, 'create'])
        ->name('prompts.versions.create');
    Route::post('prompts/{prompt}/versions', [PromptVersionController::class, 'store'])
        ->name('prompts.versions.store');
    Route::get('prompts/{prompt}/versions/compare', [PromptVersionController::class, 'compare'])
        ->name('prompts.versions.compare');
    Route::get('prompts/{prompt}/versions/{version}', [PromptVersionController::class, 'show'])
        ->name('prompts.versions.show');
    Route::post('prompts/{prompt}/versions/{version}/activate', [PromptVersionController::class, 'activate'])
        ->name('prompts.versions.activate');

    // API keys
    Route::get('api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::get('api-keys/create', [ApiKeyController::class, 'create'])->name('api-keys.create');
    Route::post('api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    // Prompt runs
    Route::get('prompts/{prompt}/runs', [PromptRunController::class, 'index'])->name('prompt-runs.index');
    Route::get('prompts/{prompt}/runs/create', [PromptRunController::class, 'create'])->name('prompt-runs.create');
    Route::post('prompts/{prompt}/runs', [PromptRunController::class, 'store'])->name('prompt-runs.store');
    Route::get('prompts/{prompt}/runs/{run}', [PromptRunController::class, 'show'])->name('prompt-runs.show');

    // LLM response actions
    Route::post('runs/{run}/responses/{response}/rate', [LlmResponseController::class, 'rate'])->name('llm-responses.rate');
    Route::get('runs/{run}/responses/{response}/export', [LlmResponseController::class, 'export'])->name('llm-responses.export');

    // Response library
    Route::get('library', [LibraryController::class, 'index'])->name('library.index');
    Route::get('library/create', [LibraryController::class, 'create'])->name('library.create');
    Route::get('library/compare', [LibraryController::class, 'compare'])->name('library.compare');
    Route::post('library', [LibraryController::class, 'store'])->name('library.store');
    Route::get('library/{library}', [LibraryController::class, 'show'])->name('library.show');
    Route::get('library/{library}/edit', [LibraryController::class, 'edit'])->name('library.edit');
    Route::put('library/{library}', [LibraryController::class, 'update'])->name('library.update');
    Route::delete('library/{library}', [LibraryController::class, 'destroy'])->name('library.destroy');
    Route::get('library/{library}/export', [LibraryController::class, 'export'])->name('library.export');

    // Stories
    Route::resource('stories', StoryController::class);
    Route::post('stories/{story}/steps', [StoryStepController::class, 'store'])->name('story-steps.store');
    Route::delete('stories/{story}/steps/{step}', [StoryStepController::class, 'destroy'])->name('story-steps.destroy');
    Route::post('stories/{story}/steps/{step}/move-up', [StoryStepController::class, 'moveUp'])->name('story-steps.move-up');
    Route::post('stories/{story}/steps/{step}/move-down', [StoryStepController::class, 'moveDown'])->name('story-steps.move-down');

    // Admin: users + LLM providers
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('llm-providers', [LlmProviderController::class, 'index'])->name('llm-providers.index');
            Route::get('llm-providers/{provider}/edit', [LlmProviderController::class, 'edit'])->name('llm-providers.edit');
            Route::put('llm-providers/{provider}', [LlmProviderController::class, 'update'])->name('llm-providers.update');
        });
    });
});

require __DIR__.'/auth.php';
