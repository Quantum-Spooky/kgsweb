<?php
/**
 * Route Alias Service
 *
 * Responsibility:
 * - Load the cached Alias mapping created by the Worker.
 * - Register aliases into the Router for instant resolution.
 */

class RouteAliasService
{
    public function sync(?string $sheetId, Router $router): void
    {
        // 1. Path to the cache created by the Worker
        $cachePath = ROOT_PATH . 'kgs-cache/google/aliases_map.json';

        if (!file_exists($cachePath)) {
            // Log that the worker needs to run, but don't crash the site.
            return;
        }

        // 2. Load the JSON map (Instant local read)
        $aliasMap = json_decode(file_get_contents($cachePath), true);

        if (!is_array($aliasMap)) {
            return;
        }

        // 3. Register each alias into the Router
        // The Router expects [ 'alias' => 'route' ] pairs
        foreach ($aliasMap as $alias => $route) {
            $router->alias($alias, $route);
        }
    }
}