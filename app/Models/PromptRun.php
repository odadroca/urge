<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptRun extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'prompt_id',
        'prompt_version_id',
        'rendered_content',
        'variables_used',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'variables_used' => 'array',
        'created_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PromptRun $run) {
            if (empty($run->created_at)) {
                $run->created_at = now();
            }
        });
    }

    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }

    public function version()
    {
        return $this->belongsTo(PromptVersion::class, 'prompt_version_id');
    }

    public function responses()
    {
        return $this->hasMany(LlmResponse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
