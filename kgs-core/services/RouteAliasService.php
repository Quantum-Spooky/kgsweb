<?php

class RouteAliasService
{
    public function sync(string $sheetId, Router $router): void
    {
        if (!$sheetId) {
            return;
        }

        $snapshotKey = 'route_aliases_snapshot_' . md5($sheetId);

        /*
        |--------------------------------------------------------------------------
        | 1. GOOGLE CLIENT
        |--------------------------------------------------------------------------
        */
        $client = ServiceContainer::get('google_client');

        if (!$client) {
            throw new RuntimeException('Google client not registered');
        }

        $rows = $client->getSheetRows($sheetId);

        /*
        |--------------------------------------------------------------------------
        | 2. LOAD PREVIOUS SNAPSHOT (FOR DIFFING)
        |--------------------------------------------------------------------------
        */
        $previous = GoogleDriveCache::getRaw('misc', $snapshotKey);

        $previousMap = [];

        if (is_array($previous) && !empty($previous['data'])) {
            foreach ($previous['data'] as $row) {

                if (!empty($row['alias']) && !empty($row['route'])) {
                    $previousMap[$row['alias']] = trim($row['route'], '/');
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. BUILD CURRENT MAP
        |--------------------------------------------------------------------------
        */
        $currentMap = [];
        $normalizedRows = [];

        foreach ($rows as $row) {

            $alias = trim($row['alias'] ?? $row[0] ?? '');
            $route = trim($row['route'] ?? $row[1] ?? '');

            if (!$alias || !$route) {
                continue;
            }

            $currentMap[$alias] = $route;

            $normalizedRows[] = [
                'alias' => $alias,
                'route' => $route
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. TRUE DIFF DETECTION
        |--------------------------------------------------------------------------
        */
        $changedRoutes = [];

        // added or changed
        foreach ($currentMap as $alias => $route) {

            $oldRoute = $previousMap[$alias] ?? null;

            if ($oldRoute !== $route) {

                $changedRoutes[] = $route;

                // also invalidate old route if it changed
                if ($oldRoute) {
                    $changedRoutes[] = $oldRoute;
                }
            }
        }

        // removed aliases
        foreach ($previousMap as $alias => $route) {
            if (!isset($currentMap[$alias])) {
                $changedRoutes[] = $route;
            }
        }

        $changedRoutes = array_values(array_unique($changedRoutes));

        /*
        |--------------------------------------------------------------------------
        | 5. INVALIDATE ONLY AFFECTED CMS PAGES
        |--------------------------------------------------------------------------
        */
        if (!empty($changedRoutes)) {
            CMSCache::invalidateMany($changedRoutes);
        }

        /*
        |--------------------------------------------------------------------------
        | 6. UPDATE SNAPSHOT (SOURCE OF TRUTH)
        |--------------------------------------------------------------------------
        */
        GoogleDriveCache::set(
            'misc',
            $snapshotKey,
            $normalizedRows,
            [
                'type' => 'route_alias_snapshot'
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 7. APPLY TO ROUTER
        |--------------------------------------------------------------------------
        */
        $router->loadAliasesFromSheet($normalizedRows);
    }
}