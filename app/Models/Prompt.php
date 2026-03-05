<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Prompt extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'tags',
        'active_version_id',
        'created_by',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Prompt $prompt) {
            if (empty($prompt->slug)) {
                $base = Str::slug($prompt->name);
                $slug = $base;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $counter++;
                }
                $prompt->slug = $slug;
            }
        });
    }

    public function activeVersion()
    {
        return $this->belongsTo(PromptVersion::class, 'active_version_id');
    }

    public function versions()
    {
        return $this->hasMany(PromptVersion::class)->orderByDesc('version_number');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function runs()
    {
        return $this->hasMany(PromptRun::class)->orderByDesc('created_at');
    }
}
