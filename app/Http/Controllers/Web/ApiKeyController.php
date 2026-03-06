<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Prompt;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function __construct(private ApiKeyService $apiKeyService) {}

    public function index()
    {
        $keys = auth()->user()->apiKeys()->with('prompts')->latest()->get();
        return view('api-keys.index', compact('keys'));
    }

    public function create()
    {
        $prompts = Prompt::orderBy('name')->get(['id', 'name', 'slug']);
        return view('api-keys.create', compact('prompts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'prompt_ids' => ['nullable', 'array'],
            'prompt_ids.*' => ['integer', 'exists:prompts,id'],
        ]);

        $rawKey = $this->apiKeyService->createForUser(
            auth()->user(),
            $data['name'],
            isset($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at']) : null
        );

        if (!empty($data['prompt_ids'])) {
            $newKey = $this->apiKeyService->findByRawKey($rawKey);
            $newKey?->prompts()->sync($data['prompt_ids']);
        }

        return redirect()
            ->route('api-keys.index')
            ->with('new_key', $rawKey)
            ->with('success', 'API key created successfully.');
    }

    public function destroy(ApiKey $apiKey)
    {
        $this->authorize('delete', $apiKey);
        $apiKey->delete();

        return redirect()->route('api-keys.index')->with('success', 'API key revoked.');
    }

    public function rotate(ApiKey $apiKey)
    {
        $this->authorize('rotate', $apiKey);

        $overlap = config('urge.key_rotation_overlap_hours', 24);

        // Create new key for the same user
        $rawKey = $this->apiKeyService->createForUser(
            $apiKey->user,
            $apiKey->name . ' (rotated)',
            $apiKey->expires_at
        );

        // Scope the new key to the same prompts if scoped
        $newKey = $this->apiKeyService->findByRawKey($rawKey);
        if ($newKey && $apiKey->prompts()->exists()) {
            $newKey->prompts()->sync($apiKey->prompts()->pluck('prompts.id'));
        }

        // Set old key to expire after overlap window
        $apiKey->update(['expires_at' => now()->addHours($overlap)]);

        return redirect()
            ->route('api-keys.index')
            ->with('new_key', $rawKey)
            ->with('success', "Key rotated. The old key will expire in {$overlap} hours.");
    }
}
