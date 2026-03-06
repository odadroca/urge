<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LibraryEntry;
use App\Models\LlmProvider;
use App\Models\Prompt;
use App\Models\PromptRun;
use App\Services\LlmDispatchService;
use App\Services\TemplateEngine;
use Illuminate\Http\Request;

class PromptRunController extends Controller
{
    public function __construct(
        private TemplateEngine $engine,
        private LlmDispatchService $dispatcher,
    ) {}

    public function index(Prompt $prompt)
    {
        $runs = $prompt->runs()->with(['creator', 'version', 'responses.provider'])->orderByDesc('created_at')->paginate(20);
        return view('prompt-runs.index', compact('prompt', 'runs'));
    }

    public function create(Prompt $prompt)
    {
        if (!$prompt->activeVersion) {
            return redirect()->route('prompts.show', $prompt)
                ->with('error', 'This prompt has no active version to run.');
        }

        $providers = LlmProvider::where('enabled', true)->orderBy('sort_order')->get();
        return view('prompt-runs.create', compact('prompt', 'providers'));
    }

    public function store(Request $request, Prompt $prompt)
    {
        if (!$prompt->activeVersion) {
            return redirect()->route('prompts.show', $prompt)
                ->with('error', 'This prompt has no active version to run.');
        }

        $validated = $request->validate([
            'variables'   => ['sometimes', 'array'],
            'variables.*' => ['nullable', 'string'],
            'providers'        => ['required', 'array', 'min:1'],
            'providers.*'      => ['integer', 'exists:llm_providers,id'],
            'save_to_library'  => ['sometimes', 'boolean'],
        ]);

        $variables = $validated['variables'] ?? [];
        $result = $this->engine->render($prompt->activeVersion->content, $variables);

        $run = PromptRun::create([
            'prompt_id'          => $prompt->id,
            'prompt_version_id'  => $prompt->activeVersion->id,
            'rendered_content'   => $result['rendered'],
            'variables_used'     => $variables,
            'created_by'         => auth()->id(),
        ]);

        $selectedProviders = LlmProvider::whereIn('id', $validated['providers'])
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($selectedProviders as $provider) {
            try {
                $llmResult = $this->dispatcher->dispatch($provider, $result['rendered']);

                \App\Models\LlmResponse::create([
                    'prompt_run_id'   => $run->id,
                    'llm_provider_id' => $provider->id,
                    'model_used'      => $llmResult->modelUsed ?? $provider->model,
                    'response_text'   => $llmResult->text,
                    'input_tokens'    => $llmResult->inputTokens,
                    'output_tokens'   => $llmResult->outputTokens,
                    'duration_ms'     => $llmResult->durationMs,
                    'status'          => $llmResult->success ? 'success' : 'error',
                    'error_message'   => $llmResult->error,
                ]);
            } catch (\Throwable $e) {
                \App\Models\LlmResponse::create([
                    'prompt_run_id'   => $run->id,
                    'llm_provider_id' => $provider->id,
                    'model_used'      => $provider->model,
                    'status'          => 'error',
                    'error_message'   => $e->getMessage(),
                    'duration_ms'     => 0,
                ]);
            }
        }

        // Auto-save successful responses to Library
        if (!empty($validated['save_to_library'])) {
            $run->load('responses');
            foreach ($run->responses as $response) {
                if ($response->status === 'success') {
                    LibraryEntry::create([
                        'prompt_id'         => $run->prompt_id,
                        'prompt_version_id' => $run->prompt_version_id,
                        'llm_provider_id'   => $response->llm_provider_id,
                        'model_used'        => $response->model_used,
                        'response_text'     => $response->response_text,
                        'created_by'        => auth()->id(),
                    ]);
                }
            }
        }

        return redirect()->route('prompt-runs.show', [$prompt, $run])
            ->with('success', 'Run completed.');
    }

    public function show(Prompt $prompt, PromptRun $run)
    {
        if ($run->prompt_id !== $prompt->id) {
            abort(404);
        }

        $run->load(['version', 'responses.provider', 'creator']);
        return view('prompt-runs.show', compact('prompt', 'run'));
    }
}
