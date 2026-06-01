<?php
/**
 * COMPONENT RENDERING ENGINE
 *
 * Responsibility:
 * - Maps component type → PHP component file
 * - Renders UI only
 *
 * Input:
 *   [
 *     'type' => string,
 *     'data' => array
 *   ]
 *
 * Rules:
 * - MUST NOT load pages
 * - MUST NOT use cache
 * - MUST NOT call CMS
 *
 * This layer is PURE PRESENTATION ONLY.
 */

function render_component(string $type, array $data = []): void
{
    /*
    |--------------------------------------------------------------------------
    | NORMALIZE
    |--------------------------------------------------------------------------
    */

    $type = trim($type);
	
    if ($type === '') {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | SECURITY
    |--------------------------------------------------------------------------
    */

    $type = str_replace(['..', '\\', '/'], '', $type);

    /*
    |--------------------------------------------------------------------------
    | COMPONENT FILE
    |--------------------------------------------------------------------------
    */

    $componentFile = ROOT_PATH . 'app/components/' . $type . '.php';

    /*
    |--------------------------------------------------------------------------
    | MISSING COMPONENT
    |--------------------------------------------------------------------------
    */

    if (!file_exists($componentFile)) {

        echo '<div class="container my-5">';
        echo '<div class="alert alert-danger">';
        echo 'Missing component: <strong>' . htmlspecialchars($type) . '</strong>';
        echo '</div>';
        echo '</div>';

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | SCHEMA (SAFE LOAD)
    |--------------------------------------------------------------------------
    */

    $schema = ComponentSchema::getSchema($type);
    $fields = $schema['fields'] ?? [];

    /*
    |--------------------------------------------------------------------------
    | DEFAULT MERGE
    |--------------------------------------------------------------------------
    */

    $defaults = [];

    foreach ($fields as $field => $config) {
        $defaults[$field] = $config['default'] ?? null;
    }

    $data = array_merge($defaults, $data);

    /*
    |--------------------------------------------------------------------------
    | REQUIRED FIELD WARNING (DEV ONLY)
    |--------------------------------------------------------------------------
    */

    foreach ($fields as $field => $config) {
        if (($config['required'] ?? false) === true) {
            if (!isset($data[$field]) || $data[$field] === '') {
                echo "<script>console.warn('Missing required field: {$field} in {$type}')</script>";
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DATA SCOPE
    |--------------------------------------------------------------------------
    */

    extract($data, EXTR_SKIP);

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    try {
		include $componentFile;
	} catch (Throwable $e) {
		echo "<div class='alert alert-danger'>Component crash: "
			. htmlspecialchars($e->getMessage())
			. "</div>";
	}
}