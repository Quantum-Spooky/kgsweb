<?php
/**
 * CMS CACHE LAYER (DOMAIN-SPECIFIC CACHE WRAPPER)
 *
 * Responsibility:
 * - Cache ONLY CMS page payloads
 * - Uses GoogleDriveCache as low-level storage engine
 *
 * Stores:
 *   [
 *     meta,
 *     components
 *   ]
 *
 * Rules:
 * - MUST NOT know filesystem structure (kgs-content)
 * - MUST NOT load pages directly
 * - MUST NOT perform normalization logic beyond shape enforcement
 * - MUST NOT render or interpret components
 *
 * This is a PURE CACHE ADAPTER for CMS domain objects.
 */

class CMSCache
{
    private static string $group = 'cms_pages_cache';

    public static function get(string $route): ?array
    {
        $cached = GoogleDriveCache::get(
            self::$group,
            self::key($route),
            3600
        );

        if (!is_array($cached)) {
            return null;
        }

        // STRICT CONTRACT: must always be CMS page shape
        return [
            'meta' => $cached['meta'] ?? [],
            'components' => $cached['components'] ?? []
        ];
    }

    public static function set(string $route, array $page): void
    {
        GoogleDriveCache::set(
            self::$group,
            self::key($route),
            $page,
            [
                'type' => 'cms_page',
                'route' => $route,
                'hash' => md5(json_encode($page)),
                'updated_at' => time()
            ]
        );
    }

    public static function forget(string $route): void
    {
        GoogleDriveCache::delete(self::$group, self::key($route));
    }

    public static function invalidateMany(array $routes): void
    {
        foreach ($routes as $route) {
            self::forget($route);
        }
    }

    private static function key(string $route): string
    {
        return md5(trim($route, '/'));
    }
}