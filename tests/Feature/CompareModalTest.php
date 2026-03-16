<?php

namespace Tests\Feature;

use App\Models\LlmProvider;
use App\Models\LlmResponse;
use App\Models\Prompt;
use App\Models\PromptRun;
use App\Models\PromptVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompareModalTest extends TestCase
{
    use RefreshDatabase;

    private function createEditorUser(): User
    {
        return User::factory()->create(['role' => 'editor']);
    }

    private function createRunWithResponses(User $user, int $responseCount = 2): array
    {
        $prompt = Prompt::create([
            'name' => 'Compare Test Prompt',
            'created_by' => $user->id,
        ]);

        $version = PromptVersion::create([
            'prompt_id' => $prompt->id,
            'version_number' => 1,
            'content' => 'Hello {{name}}',
            'variables' => ['name'],
            'created_by' => $user->id,
        ]);

        $prompt->update(['active_version_id' => $version->id]);

        $run = PromptRun::create([
            'prompt_id' => $prompt->id,
            'prompt_version_id' => $version->id,
            'rendered_content' => 'Hello World',
            'variables_used' => ['name' => 'World'],
            'created_by' => $user->id,
        ]);

        $responses = [];
        for ($i = 0; $i < $responseCount; $i++) {
            $provider = LlmProvider::create([
                'driver' => 'openai',
                'name' => 'Provider ' . ($i + 1),
                'model' => 'gpt-4',
                'enabled' => true,
                'sort_order' => $i,
            ]);

            $responses[] = LlmResponse::create([
                'prompt_run_id' => $run->id,
                'llm_provider_id' => $provider->id,
                'model_used' => 'gpt-4',
                'response_text' => 'Response from provider ' . ($i + 1),
                'status' => 'success',
                'duration_ms' => 1000 + ($i * 500),
                'input_tokens' => 100,
                'output_tokens' => 200 + ($i * 50),
            ]);
        }

        return compact('prompt', 'version', 'run', 'responses');
    }

    public function test_run_show_page_renders_with_compare_checkboxes(): void
    {
        $user = $this->createEditorUser();
        $data = $this->createRunWithResponses($user, 2);

        $response = $this->actingAs($user)
            ->get(route('prompt-runs.show', [$data['prompt'], $data['run']]));

        $response->assertOk();
        $response->assertSee('Compare Selected', false);
        $response->assertSee('compareIds', false);
    }

    public function test_run_show_page_includes_response_data_for_modal(): void
    {
        $user = $this->createEditorUser();
        $data = $this->createRunWithResponses($user, 3);

        $response = $this->actingAs($user)
            ->get(route('prompt-runs.show', [$data['prompt'], $data['run']]));

        $response->assertOk();
        // Verify that response data is embedded for the compare modal
        $response->assertSee('allResponses', false);
        $response->assertSee('Provider 1', false);
        $response->assertSee('Provider 2', false);
        $response->assertSee('Provider 3', false);
    }

    public function test_single_response_does_not_show_compare_ui(): void
    {
        $user = $this->createEditorUser();
        $data = $this->createRunWithResponses($user, 1);

        $response = $this->actingAs($user)
            ->get(route('prompt-runs.show', [$data['prompt'], $data['run']]));

        $response->assertOk();
        // Compare modal should not be rendered with a single response
        $response->assertDontSee('allResponses', false);
    }

    public function test_version_compare_page_has_word_diff_toggle(): void
    {
        $user = $this->createEditorUser();

        $prompt = Prompt::create([
            'name' => 'Diff Test Prompt',
            'created_by' => $user->id,
        ]);

        PromptVersion::create([
            'prompt_id' => $prompt->id,
            'version_number' => 1,
            'content' => 'Hello world, this is version one.',
            'variables' => [],
            'created_by' => $user->id,
        ]);

        PromptVersion::create([
            'prompt_id' => $prompt->id,
            'version_number' => 2,
            'content' => 'Hello world, this is version two.',
            'variables' => [],
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('prompts.versions.compare', [$prompt, 'v1' => 1, 'v2' => 2]));

        $response->assertOk();
        // Should have word/char diff toggle buttons
        $response->assertSee('Words', false);
        $response->assertSee('Chars', false);
        $response->assertSee('Lines', false);
        $response->assertSee('diffViewer', false);
    }

    public function test_version_index_has_quick_diff_button(): void
    {
        $user = $this->createEditorUser();

        $prompt = Prompt::create([
            'name' => 'Quick Diff Prompt',
            'created_by' => $user->id,
        ]);

        PromptVersion::create([
            'prompt_id' => $prompt->id,
            'version_number' => 1,
            'content' => 'Version 1 content',
            'variables' => [],
            'created_by' => $user->id,
        ]);

        PromptVersion::create([
            'prompt_id' => $prompt->id,
            'version_number' => 2,
            'content' => 'Version 2 content',
            'variables' => [],
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('prompts.versions.index', $prompt));

        $response->assertOk();
        $response->assertSee('Quick Diff', false);
        $response->assertSee('Full diff', false);
        $response->assertSee('versionContents', false);
    }

    public function test_version_create_page_has_editor_mode_toggle(): void
    {
        $user = $this->createEditorUser();

        $prompt = Prompt::create([
            'name' => 'Editor Mode Prompt',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('prompts.versions.create', $prompt));

        $response->assertOk();
        $response->assertSee('editorMode', false);
        $response->assertSee('switchMode', false);
        $response->assertSee('Visual', false);
        $response->assertSee('autocomplete', false);
    }
}
