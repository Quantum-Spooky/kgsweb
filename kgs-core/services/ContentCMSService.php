<?php
/**
 * CMS SERVICE LAYER (BRIDGE)
 *
 * Responsibility:
 * - Orchestrates CMS + Cache access
 * - Decides when to use CMSCache vs ContentCMS
 * - Normalizes CMS output into router-ready format
 *
 * Flow:
 *   1. Try CMSCache::get(route)
 *   2. If cache miss → ContentCMS::loadPage(route)
 *   3. Store result via CMSCache::set(route, page)
 *   4. Return normalized page array
 *
 * Rules:
 * - MUST NOT directly access filesystem pages
 * - MUST NOT implement caching logic (delegates to CMSCache)
 * - MUST NOT render components
 */

class ContentCMSService
{
    public function load(string $route): array
    {
        /*
        |--------------------------------------------------------------------------
        | CMS ENGINE LOAD
        |--------------------------------------------------------------------------
        */
        $cmsPath = ROOT_PATH . 'kgs-core/cms/ContentCMS.php';

        if (!file_exists($cmsPath)) {
            return [
                'meta' => [],
                'components' => []
            ];
        }

        require_once $cmsPath;

        $page = ContentCMS::loadPage($route);

        if (!is_array($page)) {
            return [
			  'meta' => [],
			  'components' => [],
			  'path' => ''
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | NORMALIZATION
        |--------------------------------------------------------------------------
        */
        return [
            'meta' => $page['meta'] ?? [],
            'components' => $page['components'] ?? []
        ];
    }
}
