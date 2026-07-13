<?php
/**
 * CONTENT SOURCE OF TRUTH (CMS CORE)
 *
 * Responsibility:
 * - Load page content from canonical filesystem source: kgs-content/pages/
 * - Return normalized structure:
 *      [
 *          'meta' => [...],
 *          'components' => [...]
 *      ]
 *
 * Rules:
 * - MUST NOT read from cache
 * - MUST NOT write to cache
 * - MUST NOT know about GoogleDriveCache or CMSCache
 * - MUST NOT depend on routing logic
 *
 * This file is PURE CONTENT LAYER ONLY.
 * If content is not found here, it does not exist.
 */

class ContentCMS
{
    public static function loadPage(string $path)
    {
        $path = trim($path, '/');

        $basePath = ROOT_PATH . 'kgs-content/pages/' . $path;

        $contentFile    = $basePath . '/content.html';
        $metaFile       = $basePath . '/meta.json';
        $componentsFile = $basePath . '/components.json';

        /*
        |--------------------------------------------------------------------------
        | NOT FOUND
        |--------------------------------------------------------------------------
        */
        if (!file_exists($contentFile) && !file_exists($componentsFile)) {
            return [
                'meta' => [
                    'title' => 'Not Found',
                    'description' => ''
                ],
                'components' => [
                    [
                        'type' => 'error-404',
                        'data' => []
                    ]
                ],
                'path' => $path
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | META
        |--------------------------------------------------------------------------
        */
		$meta = [];

		if (file_exists($metaFile)) {
			$decoded = json_decode(file_get_contents($metaFile), true);

			if (is_array($decoded)) {
				$meta = $decoded;
			}
		}

		$meta = array_merge([
			'title' => ucfirst(str_replace(['/', '-', '_'], ' ', $path)),
			'description' => '',
			'layout' => 'default'
		], (is_array($meta) ? $meta : [])); // Ensures file-based meta wins


        /*
        |--------------------------------------------------------------------------
        | COMPONENTS (PRIMARY)
        |--------------------------------------------------------------------------
        */
        if (file_exists($componentsFile)) {
            $decoded = json_decode(file_get_contents($componentsFile), true);

            return [
                'meta' => $meta,
                'components' => is_array($decoded) ? $decoded : [],
                'path' => $path
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | LEGACY HTML
        |--------------------------------------------------------------------------
        */
        if (file_exists($contentFile)) {
            return [
                'meta' => $meta,
                'components' => [
                    [
                        'type' => 'html',
                        'data' => [
                            'html' => file_get_contents($contentFile)
                        ]
                    ]
                ],
                'path' => $path
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        */
        return [
            'meta' => $meta,
            'components' => [
                [
                    'type' => 'error-404',
                    'data' => []
                ]
            ],
            'path' => $path
        ];
    }
	

    public static function exists(string $path): bool
    {
        $path = trim($path, '/');

        return file_exists(
            ROOT_PATH . 'kgs-content/pages/' . $path . '/components.json'
        );
    }
}