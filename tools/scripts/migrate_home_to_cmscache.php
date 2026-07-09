<?php

define('ROOT_PATH', dirname(__DIR__) . '/');

require_once ROOT_PATH . 'kgs-core/google/GoogleDriveCache.php';
require_once ROOT_PATH . 'kgs-core/services/CMSCache.php';

/*
|--------------------------------------------------------------------------
| SOURCE FILES (LEGACY)
|--------------------------------------------------------------------------
*/
$metaFile = ROOT_PATH . 'kgs-cache/drive/home/meta.json';
$componentsFile = ROOT_PATH . 'kgs-cache/drive/home/components.json';

/*
|--------------------------------------------------------------------------
| TARGET ROUTE
|--------------------------------------------------------------------------
*/
$route = 'home';

if (!file_exists($metaFile) || !file_exists($componentsFile)) {
    die("Missing legacy home files\n");
}

$meta = json_decode(file_get_contents($metaFile), true);
$components = json_decode(file_get_contents($componentsFile), true);

if (!is_array($meta) || !is_array($components)) {
    die("Invalid JSON in legacy files\n");
}

/*
|--------------------------------------------------------------------------
| BUILD CMS FORMAT (EXACT CMSCache CONTRACT)
|--------------------------------------------------------------------------
*/
$page = [
    'meta' => $meta,
    'components' => $components
];

/*
|--------------------------------------------------------------------------
| WRITE INTO CMSCache
|--------------------------------------------------------------------------
*/
CMSCache::set($route, $page);

/*
|--------------------------------------------------------------------------
| VERIFY
|--------------------------------------------------------------------------
*/
$check = CMSCache::get($route);

if ($check) {
    echo "Migration successful for route: {$route}\n";
} else {
    echo "Migration failed for route: {$route}\n";
}