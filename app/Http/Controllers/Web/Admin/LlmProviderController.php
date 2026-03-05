<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\LlmProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class LlmProviderController extends Controller
{
    public function index()
    {
        $providers = LlmProvider::orderBy('sort_order')->get();
        return view('admin.llm-providers.index', compact('providers'));
    }

    public function edit(LlmProvider $provider)
    {
        return view('admin.llm-providers.edit', compact('provider'));
    }

    public function update(Request $request, LlmProvider $provider)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'model'   => ['required', 'string', 'max:255'],
            'base_url'=> ['nullable', 'string', 'max:500'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'enabled' => ['boolean'],
        ]);

        $data = [
            'name'     => $validated['name'],
            'model'    => $validated['model'],
            'base_url' => $validated['base_url'] ?: null,
            'enabled'  => $request->boolean('enabled'),
        ];

        // Only update the key if a new value was provided
        if (!empty($validated['api_key'])) {
            $data['api_key_encrypted'] = Crypt::encryptString($validated['api_key']);
        }

        $provider->update($data);

        return redirect()->route('admin.llm-providers.index')
            ->with('success', "Provider \"{$provider->name}\" updated.");
    }
}
