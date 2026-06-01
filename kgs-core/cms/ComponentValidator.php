<?php

class ComponentValidator
{
    public static function clean(array $schema, array $input): array
    {
        $clean = [];

        $fields = $schema['fields'] ?? [];

        foreach ($fields as $field => $config) {

            $type = $config['type'] ?? 'text';
            $value = $input[$field] ?? $config['default'] ?? null;

            $clean[$field] = self::cast($type, $value, $config);
        }

        foreach ($fields as $field => $config) {
            if (!empty($config['required'])) {
                if (!isset($clean[$field]) || $clean[$field] === '') {
                    throw new Exception("Missing required field: {$field}");
                }
            }
        }

        return $clean;
    }

    private static function cast(string $type, $value, array $config)
    {
        switch ($type) {

            case 'text':
            case 'textarea':
                return trim((string)$value);

            case 'image':
                return filter_var($value, FILTER_SANITIZE_URL);

            case 'number':
            case 'range':
                return is_numeric($value)
                    ? 0 + $value
                    : ($config['default'] ?? 0);

            case 'select':
                $options = $config['options'] ?? [];
                return in_array($value, $options, true)
                    ? $value
                    : ($config['default'] ?? null);

            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            default:
                return $value;
        }
    }
}