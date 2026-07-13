<?php

require_once dirname(__DIR__, 2) . '/cfg/config.php';
require_once ROOT_PATH . 'kgs-core/cms/ComponentSchema.php';
require_once ROOT_PATH . 'kgs-core/cms/ComponentValidator.php';
require_once ROOT_PATH . 'kgs-core/cms/DriveCMS.php';

$route = $_GET['route'] ?? 'home';

/*
|--------------------------------------------------------------------------
| LOAD PAGE
|--------------------------------------------------------------------------
*/
$page = DriveCMS::loadPage($route);
$components = $page['components'] ?? [];

/*
|--------------------------------------------------------------------------
| SAVE COMPONENT
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_component'])) {

    $index = (int)($_POST['component_index'] ?? -1);
    $type  = $_POST['component_type'] ?? '';
    $data  = $_POST['data'] ?? [];

    if (!isset($components[$index])) {
        die("Invalid component index");
    }

    $schema = ComponentSchema::getSchema($type);
    $fields = $schema['fields'] ?? [];

    $cleanData = ComponentValidator::clean($schema, $data);

    $components[$index]['data'] = $cleanData;

    $file = ROOT_PATH . 'kgs-cache/drive/' . trim($route, '/') . '/components.json';

    file_put_contents(
        $file,
        json_encode($components, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    header("Location: ?route=" . urlencode($route));
    exit;
}

/*
|--------------------------------------------------------------------------
| MOVE COMPONENT
|--------------------------------------------------------------------------
*/
if (isset($_GET['move'], $_GET['index'])) {

    $index = (int)$_GET['index'];
    $direction = $_GET['move'];

    if (isset($components[$index])) {

        if ($direction === 'up' && $index > 0) {
            [$components[$index - 1], $components[$index]] =
            [$components[$index], $components[$index - 1]];
        }

        if ($direction === 'down' && $index < count($components) - 1) {
            [$components[$index + 1], $components[$index]] =
            [$components[$index], $components[$index + 1]];
        }

        $file = ROOT_PATH . 'kgs-cache/drive/' . trim($route, '/') . '/components.json';

        file_put_contents(
            $file,
            json_encode($components, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    header("Location: ?route=" . urlencode($route));
    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE COMPONENT
|--------------------------------------------------------------------------
*/
if (isset($_GET['delete'], $_GET['index'])) {

    $index = (int)$_GET['index'];

    if (isset($components[$index])) {
        unset($components[$index]);
        $components = array_values($components);
    }

    $file = ROOT_PATH . 'kgs-cache/drive/' . trim($route, '/') . '/components.json';

    file_put_contents(
        $file,
        json_encode($components, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    header("Location: ?route=" . urlencode($route));
    exit;
}

/*
|--------------------------------------------------------------------------
| ADD COMPONENT
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_component'])) {

    $newType = $_POST['new_type'] ?? '';

    $schema = ComponentSchema::getSchema($newType);
    $fields = $schema['fields'] ?? [];

    if ($newType === '' || empty($fields)) {
        die("Invalid component type");
    }

    $defaultData = [];

    foreach ($fields as $field => $config) {
        $defaultData[$field] = $config['default'] ?? null;
    }

    $components[] = [
        'type' => $newType,
        'data' => $defaultData
    ];

    $file = ROOT_PATH . 'kgs-cache/drive/' . trim($route, '/') . '/components.json';

    file_put_contents(
        $file,
        json_encode($components, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    header("Location: ?route=" . urlencode($route));
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Page Editor - <?= htmlspecialchars($route) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

    <h1 class="mb-4">Editing: <?= htmlspecialchars($route) ?></h1>

    <div class="card mb-4">
        <div class="card-body">
            <form method="post" class="d-flex gap-2">
                <select name="new_type" class="form-select" required>
                    <option value="">Select component</option>
                    <option value="hero">Hero</option>
                    <option value="live-feed">Live Feed</option>
                </select>

                <button name="add_component" class="btn btn-success">
                    Add Component
                </button>
            </form>
        </div>
    </div>

    <?php foreach ($components as $index => $component): ?>

        <?php
        $type = $component['type'];
        $data = $component['data'] ?? [];

        $schema = ComponentSchema::getSchema($type);
        $fields = $schema['fields'] ?? [];

        $data = array_merge(
            array_map(fn($f) => $f['default'] ?? null, $fields),
            $data
        );
        ?>

        <div class="card mb-4">

            <div class="card-header d-flex justify-content-between align-items-center">

                <strong><?= htmlspecialchars($type) ?></strong>

                <div class="btn-group">
                    <a class="btn btn-sm btn-outline-secondary"
                       href="?route=<?= urlencode($route) ?>&move=up&index=<?= $index ?>">↑</a>

                    <a class="btn btn-sm btn-outline-secondary"
                       href="?route=<?= urlencode($route) ?>&move=down&index=<?= $index ?>">↓</a>

                    <a class="btn btn-sm btn-outline-danger"
                       href="?route=<?= urlencode($route) ?>&delete=1&index=<?= $index ?>"
                       onclick="return confirm('Delete this component?')">✕</a>
                </div>

            </div>

            <div class="card-body">

                <form method="post">

                    <input type="hidden" name="component_index" value="<?= $index ?>">
                    <input type="hidden" name="component_type" value="<?= htmlspecialchars($type) ?>">

                    <?php foreach ($fields as $field => $config): ?>

                        <?php
                        $fieldType = $config['type'] ?? 'text';
                        $value = $data[$field] ?? '';
                        ?>

                        <div class="mb-3">

                            <label class="form-label">
                                <?= htmlspecialchars($config['label'] ?? ucfirst($field)) ?>
                            </label>

                            <?php if ($fieldType === 'text'): ?>
                                <input type="text" class="form-control"
                                       name="data[<?= $field ?>]"
                                       value="<?= htmlspecialchars($value) ?>">

                            <?php elseif ($fieldType === 'textarea'): ?>
                                <textarea class="form-control"
                                          name="data[<?= $field ?>]"><?= htmlspecialchars($value) ?></textarea>

                            <?php elseif ($fieldType === 'image'): ?>
                                <input type="text" class="form-control"
                                       name="data[<?= $field ?>]"
                                       value="<?= htmlspecialchars($value) ?>">

                            <?php elseif ($fieldType === 'select'): ?>
                                <select class="form-select" name="data[<?= $field ?>]">
                                    <?php foreach ($config['options'] as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $value === $opt ? 'selected' : '' ?>>
                                            <?= ucfirst($opt) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($fieldType === 'range'): ?>
                                <input type="range"
                                       class="form-range"
                                       name="data[<?= $field ?>]"
                                       min="<?= $config['min'] ?>"
                                       max="<?= $config['max'] ?>"
                                       step="<?= $config['step'] ?>"
                                       value="<?= htmlspecialchars($value) ?>">
                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                    <button name="save_component" class="btn btn-primary">Save</button>

                </form>

            </div>
        </div>

    <?php endforeach; ?>

</div>

</body>
</html>