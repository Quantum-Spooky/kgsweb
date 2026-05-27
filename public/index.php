<?php

require_once dirname(__DIR__) . '/kgs-core/bootstrap.php';
require_once ROOT_PATH . 'kgs-core/core/Router.php';

$router = new Router();

/*
|--------------------------------------------------------------------------
| AUTO LOAD LOCAL PAGE TEMPLATES
|--------------------------------------------------------------------------
|
| Fallback/local PHP pages
|
*/

$router->loadFromDirectory(ROOT_PATH . 'includes/pages');

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
| DETERMINE ROUTE
|--------------------------------------------------------------------------
|
| Supports:
|   /?route=about
|   /about
|
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
| DISPATCH
|--------------------------------------------------------------------------
*/

$router->dispatch($route);
