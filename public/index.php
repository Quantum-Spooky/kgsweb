<?php

require_once dirname(__DIR__) . '/kgs-core/bootstrap.php';

/*
|--------------------------------------------------------------------------
| DETERMINE ROUTE
|--------------------------------------------------------------------------
*/
$route = $_GET['route'] ?? '';

if (empty($route)) {

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    $base = parse_url(BASE_URL, PHP_URL_PATH);

    if ($base && strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base));
    }

    $route = trim($uri, '/');
}

/*
|--------------------------------------------------------------------------
| LOAD CMS ALIASES
|--------------------------------------------------------------------------
*/
$sheetId = config_value('route_aliases_sheet_id');

if (!empty($sheetId) && function_exists('fetch_route_aliases_from_sheet')) {

    $aliases = fetch_route_aliases_from_sheet($sheetId);

    if (is_array($aliases)) {
        $router->loadAliasesFromSheet($aliases);
    }
}

/*
|--------------------------------------------------------------------------
| DISPATCH (ONLY ONCE)
|--------------------------------------------------------------------------
*/
$router->dispatch($route);