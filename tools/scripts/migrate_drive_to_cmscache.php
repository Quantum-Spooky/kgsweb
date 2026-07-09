<?php

require_once __DIR__ . '/../../kgs-core/bootstrap.php';

$base = ROOT_PATH . 'kgs-content/pages/';

$routes = array_filter(glob($base . '*'), 'is_dir');

foreach ($routes as $routeDir) {

    $route = basename($routeDir);

    $metaFile = $routeDir . '/meta.json';
    $componentsFile = $routeDir . '/components.json';

    if (!file_exists($metaFile) || !file_exists($componentsFile)) {
        continue;
    }

    $meta = json_decode(file_get_contents($metaFile), true) ?? [];
    $components = json_decode(file_get_contents($componentsFile), true) ?? [];

    $payload = [
        'meta' => $meta,
        'components' => $components
    ];

    CMSCache::set($route, $payload);

    echo "Migrated: $route\n";
}