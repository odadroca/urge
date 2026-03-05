<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function __construct(private ApiKeyService $apiKeyService) {}

    public function index()
    {
        $keys = auth()->user()->apiKeys()->latest()->get();
        return view('api-keys.index', compact('keys'));
    }

    public function create()
    {
        return view('api-keys.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $rawKey = $this->apiKeyService->createForUser(
            auth()->user(),
            $data['name'],
            isset($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at']) : null
        );

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
}
