<?php

namespace App\Services;

class TemplateEngine
{
    private const PATTERN = '/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/';

    public function extractVariables(string $content): array
    {
        preg_match_all(self::PATTERN, $content, $matches);
        return array_values(array_unique($matches[1]));
    }

    public function render(string $content, array $variables): array
    {
        $missing = [];
        $used = [];

        $rendered = preg_replace_callback(self::PATTERN, function ($matches) use ($variables, &$missing, &$used) {
            $name = $matches[1];
            if (array_key_exists($name, $variables)) {
                $used[] = $name;
                return $variables[$name];
            }
            $missing[] = $name;
            return $matches[0];
        }, $content);

        return [
            'rendered'          => $rendered,
            'variables_used'    => array_values(array_unique($used)),
            'variables_missing' => array_values(array_unique($missing)),
        ];
    }
}
