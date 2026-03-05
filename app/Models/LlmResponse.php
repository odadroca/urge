<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmResponse extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'prompt_run_id',
        'llm_provider_id',
        'model_used',
        'response_text',
        'input_tokens',
        'output_tokens',
        'duration_ms',
        'status',
        'error_message',
        'rating',
        'rated_by',
        'rated_at',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'rated_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (LlmResponse $response) {
            if (empty($response->created_at)) {
                $response->created_at = now();
            }
        });
    }

    public function run()
    {
        return $this->belongsTo(PromptRun::class, 'prompt_run_id');
    }

    public function provider()
    {
        return $this->belongsTo(LlmProvider::class, 'llm_provider_id');
    }

    public function rater()
    {
        return $this->belongsTo(User::class, 'rated_by');
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
