<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prompt_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('llm_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model_used');
            $table->longText('response_text');
            $table->text('notes')->nullable();
            $table->tinyInteger('rating')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_entries');
    }
};
