<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_run_id')->constrained('prompt_runs')->cascadeOnDelete();
            $table->foreignId('llm_provider_id')->constrained('llm_providers');
            $table->string('model_used');
            $table->longText('response_text')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('status')->default('success');
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->foreignId('rated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rated_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_responses');
    }
};
