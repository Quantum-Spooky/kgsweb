<?php

$failures = [];

function scanFile($file, $rules)
{
    global $failures;

    $content = file_get_contents($file);

    foreach ($rules as $ruleName => $patterns) {
        foreach ($patterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                $failures[] = "$file violates $ruleName ($pattern)";
            }
        }
    }
}

/*
|---------------------------------------------
| RULE SET
|---------------------------------------------
*/

$rules = [

    'Router must not access cache or content' => [
        'GoogleDriveCache::',
        'CMSCache::set',
        'ContentCMS::loadFromFile',
        'file_get_contents(kgs-content'
    ],

    'ContentCMS must not use cache' => [
        'CMSCache::',
        'GoogleDriveCache::get',
        'GoogleDriveCache::set'
    ],

    'CMSCache must not load files directly' => [
        'file_get_contents',
        'kgs-content'
    ],

    'Renderer must not load CMS or cache' => [
        'ContentCMS::',
        'CMSCache::',
        'GoogleDriveCache::'
    ],
];

/*
|---------------------------------------------
| FILES TO CHECK
|---------------------------------------------
*/

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/../../kgs-core')
);

foreach ($files as $file) {

    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    scanFile($file->getPathname(), $rules);
}

/*
|---------------------------------------------
| RESULT
|---------------------------------------------
*/

if (!empty($failures)) {
    echo "ARCHITECTURE VIOLATIONS FOUND:\n\n";
    foreach ($failures as $f) {
        echo $f . "\n";
    }
    exit(1);
}

echo "Architecture OK\n";
exit(0);