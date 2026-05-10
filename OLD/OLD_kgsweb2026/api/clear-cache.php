<?php
// kgsweb2026/api/clear-cache.php

/**
 * LITE SPEED PURGE
 * This header tells the LiteSpeed server to flush its cache 
 * so the next page load is 100% fresh from PHP.
 */
header("X-LiteSpeed-Purge: *");

header('Content-Type: application/json');

// Use absolute path relative to this specific file
$cacheDir = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

$deleted = [];
if (is_dir($cacheDir)) {
    // glob looks for any file inside the api/cache/ folder
    $files = glob($cacheDir . '*'); 
    foreach($files as $file){
        if(is_file($file)) {
            $name = basename($file);
            if(unlink($file)) {
                $deleted[] = $name;
            }
        }
    }
}

echo json_encode([
    'v' => '3.1', // Incremented version to track the Purge update
    'status' => 'success',
    'count' => count($deleted),
    'deleted_files' => $deleted,
    'folder' => $cacheDir,
    'litespeed_purge' => 'triggered'
]);
exit;