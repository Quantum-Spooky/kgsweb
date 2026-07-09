<?php

class ComponentRenderer
{
    public static function render(string $type, array $data = []): void
    {
        $type = trim($type);

        if ($type === '') {
            return;
        }

        // basic hardening
        $type = str_replace(['..', '\\', '/'], '', $type);

        $componentFile = ROOT_PATH . 'app/components/' . $type . '.php';

        if (!file_exists($componentFile)) {
            echo '<div class="alert alert-danger m-3">';
            echo 'Missing component: ' . htmlspecialchars($type);
            echo '</div>';
            return;
        }

        $schema = ComponentSchema::getSchema($type);

        $fields = $schema['fields'] ?? [];

        // apply defaults
        $defaults = [];
        foreach ($fields as $field => $config) {
            $defaults[$field] = $config['default'] ?? null;
        }

        $data = array_merge($defaults, $data);

        // basic required-field warnings (non-fatal)
        foreach ($fields as $field => $config) {
            if (($config['required'] ?? false) === true) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    error_log("Missing required field: {$field} in {$type}");
                }
            }
        }

        extract($data, EXTR_SKIP);

        include $componentFile;
    }
}