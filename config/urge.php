<?php

return [
    'key_prefix'                  => env('URGE_KEY_PREFIX', 'urge_'),
    'key_bytes'                   => env('URGE_KEY_BYTES', 31),
    'key_preview_length'          => 8,
    'variable_pattern'            => '/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/',
    'include_pattern'             => '/\{\{>([a-zA-Z0-9_-]+)\}\}/',
    'max_include_depth'           => (int) env('URGE_MAX_INCLUDE_DEPTH', 10),
    'api_rate_limit'              => env('URGE_API_RATE_LIMIT', 60),
    'api_rate_window'             => env('URGE_API_RATE_WINDOW', 60),
    'key_rotation_overlap_hours'  => env('URGE_KEY_ROTATION_OVERLAP_HOURS', 24),
    'default_environments'        => ['production', 'staging'],
];
