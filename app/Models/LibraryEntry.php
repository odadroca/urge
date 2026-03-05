<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryEntry extends Model
{
    protected $fillable = [
        'prompt_id',
        'prompt_version_id',
        'llm_provider_id',
        'model_used',
        'response_text',
        'notes',
        'rating',
        'created_by',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }

    public function version()
    {
        return $this->belongsTo(PromptVersion::class, 'prompt_version_id');
    }

    public function provider()
    {
        return $this->belongsTo(LlmProvider::class, 'llm_provider_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
