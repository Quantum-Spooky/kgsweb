<?php

class DriveCMS
{
    public static function loadPage(string $path)
    {
        $path = trim($path, '/');

        $basePath = ROOT_PATH . 'kgs-cache/drive/' . $path;

        $contentFile = $basePath . '/content.html';
        $metaFile    = $basePath . '/meta.json';

        /*
        |--------------------------------------------------------------------------
        | FILE NOT FOUND (NOT SYNCED YET)
        |--------------------------------------------------------------------------
        */
        if (!file_exists($contentFile)) {
            return [
                'content' => '<h1>404 - Page Not Found</h1>',
                'meta' => [
                    'title' => 'Not Found'
                ]
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | LOAD CONTENT
        |--------------------------------------------------------------------------
        */
        $content = file_get_contents($contentFile);

        /*
        |--------------------------------------------------------------------------
        | LOAD META (OPTIONAL)
        |--------------------------------------------------------------------------
        */
        $meta = [];

        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);

            if (!is_array($meta)) {
                $meta = [];
            }
        }

        return [
            'content' => $content,
            'meta' => $meta,
            'path' => $path
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | OPTIONAL: HELPER FOR DEBUGGING CACHE STATE
    |--------------------------------------------------------------------------
    */
    public static function exists(string $path): bool
    {
        $file = ROOT_PATH . 'kgs-cache/drive/' . trim($path, '/') . '/content.html';
        return file_exists($file);
    }
}