<?php

return [
    'key_prefix'          => env('URGE_KEY_PREFIX', 'urge_'),
    'key_bytes'           => env('URGE_KEY_BYTES', 31),
    'key_preview_length'  => 8,
    'variable_pattern'    => '/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/',
];
