<?php

namespace Tests\Feature;

use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariableMetadataTest extends TestCase
{
    use RefreshDatabase;

    private function createEditorUser(): User
    {
        return User::factory()->create(['role' => 'editor']);
    }

    private function createPromptWithVersion(User $user): Prompt
    {
        $prompt = Prompt::create([
            'name' => 'Metadata Test Prompt',
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

        return $prompt;
    }

    public function test_version_created_with_variable_metadata(): void
    {
        $user = $this->createEditorUser();
        $prompt = $this->createPromptWithVersion($user);

        $response = $this->actingAs($user)
            ->post(route('prompts.versions.store', $prompt), [
                'content' => 'Hello {{name}}, welcome to {{city}}',
                'commit_message' => 'Added city variable',
                'variable_metadata' => [
                    'name' => [
                        'type' => 'string',
                        'default' => 'World',
                        'description' => 'User name',
                    ],
                    'city' => [
                        'type' => 'enum',
                        'default' => 'NYC',
                        'description' => 'Target city',
                        'options_csv' => 'NYC, London, Tokyo',
                    ],
                ],
            ]);

        $response->assertRedirect();

        $version = PromptVersion::where('prompt_id', $prompt->id)
            ->where('version_number', 2)
            ->first();

        $this->assertNotNull($version);
        $this->assertEquals(['name', 'city'], $version->variables);

        $meta = $version->variable_metadata;
        $this->assertNotNull($meta);
        $this->assertEquals('string', $meta['name']['type']);
        $this->assertEquals('World', $meta['name']['default']);
        $this->assertEquals('User name', $meta['name']['description']);

        $this->assertEquals('enum', $meta['city']['type']);
        $this->assertEquals('NYC', $meta['city']['default']);
        $this->assertEquals(['NYC', 'London', 'Tokyo'], $meta['city']['options']);
    }

    public function test_metadata_only_includes_variables_in_content(): void
    {
        $user = $this->createEditorUser();
        $prompt = $this->createPromptWithVersion($user);

        $response = $this->actingAs($user)
            ->post(route('prompts.versions.store', $prompt), [
                'content' => 'Hello {{name}}',
                'variable_metadata' => [
                    'name' => ['type' => 'string', 'default' => 'World'],
                    'nonexistent' => ['type' => 'string', 'default' => 'should be filtered'],
                ],
            ]);

        $response->assertRedirect();

        $version = PromptVersion::where('prompt_id', $prompt->id)
            ->where('version_number', 2)
            ->first();

        $this->assertArrayHasKey('name', $version->variable_metadata);
        $this->assertArrayNotHasKey('nonexistent', $version->variable_metadata);
    }

    public function test_metadata_round_trip_preserves_data(): void
    {
        $user = $this->createEditorUser();
        $prompt = $this->createPromptWithVersion($user);

        $metadata = [
            'name' => [
                'type' => 'enum',
                'default' => 'Alice',
                'description' => 'The user name',
                'options_csv' => 'Alice, Bob, Charlie',
            ],
        ];

        $this->actingAs($user)
            ->post(route('prompts.versions.store', $prompt), [
                'content' => 'Hello {{name}}',
                'variable_metadata' => $metadata,
            ]);

        $version = PromptVersion::where('prompt_id', $prompt->id)
            ->where('version_number', 2)
            ->firstOrFail();

        $meta = $version->variable_metadata;
        $this->assertEquals('enum', $meta['name']['type']);
        $this->assertEquals('Alice', $meta['name']['default']);
        $this->assertEquals('The user name', $meta['name']['description']);
        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $meta['name']['options']);
    }

    public function test_version_without_metadata_stores_null(): void
    {
        $user = $this->createEditorUser();
        $prompt = $this->createPromptWithVersion($user);

        $this->actingAs($user)
            ->post(route('prompts.versions.store', $prompt), [
                'content' => 'Hello {{name}}',
            ]);

        $version = PromptVersion::where('prompt_id', $prompt->id)
            ->where('version_number', 2)
            ->first();

        $this->assertNull($version->variable_metadata);
    }

    public function test_designer_store_handles_options_csv(): void
    {
        $user = $this->createEditorUser();
        $prompt = $this->createPromptWithVersion($user);

        $response = $this->actingAs($user)
            ->post(route('prompts.versions.designer.store', $prompt), [
                'content' => 'Hello {{tone}}',
                'commit_message' => 'Via designer',
                'variable_metadata' => [
                    'tone' => [
                        'type' => 'enum',
                        'default' => 'formal',
                        'description' => 'Response tone',
                        'options_csv' => 'formal, casual, technical',
                    ],
                ],
            ]);

        $response->assertRedirect();

        $version = PromptVersion::where('prompt_id', $prompt->id)
            ->where('version_number', 2)
            ->first();

        $this->assertNotNull($version);
        $meta = $version->variable_metadata;
        $this->assertEquals(['formal', 'casual', 'technical'], $meta['tone']['options']);
        $this->assertArrayNotHasKey('options_csv', $meta['tone']);
    }
}
