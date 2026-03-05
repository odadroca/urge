<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_providers', function (Blueprint $table) {
            $table->id();
            $table->string('driver');
            $table->string('name');
            $table->string('model');
            $table->string('base_url')->nullable();
            $table->text('api_key_encrypted')->nullable();
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('llm_providers')->insert([
            [
                'driver'     => 'openai',
                'name'       => 'OpenAI GPT-4o Mini',
                'model'      => 'gpt-4o-mini',
                'base_url'   => null,
                'enabled'    => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'driver'     => 'anthropic',
                'name'       => 'Anthropic Claude Haiku',
                'model'      => 'claude-haiku-4-5-20251001',
                'base_url'   => null,
                'enabled'    => false,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'driver'     => 'mistral',
                'name'       => 'Mistral Small',
                'model'      => 'mistral-small-latest',
                'base_url'   => null,
                'enabled'    => false,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'driver'     => 'gemini',
                'name'       => 'Google Gemini Flash',
                'model'      => 'gemini-1.5-flash',
                'base_url'   => null,
                'enabled'    => false,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'driver'     => 'ollama',
                'name'       => 'Ollama (local)',
                'model'      => 'llama3.2',
                'base_url'   => 'http://localhost:11434',
                'enabled'    => false,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_providers');
    }
};
