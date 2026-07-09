<?php
/**
 * APPLICATION FRONT CONTROLLER
 */

require_once dirname(__DIR__) . '/kgs-core/bootstrap.php';

/*
|--------------------------------------------------------------------------
| 1. DETERMINE ROUTE
|--------------------------------------------------------------------------
*/
$route = $_GET['route'] ?? '';

if (empty($route)) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $base = $base = parse_url(config('base_url'), PHP_URL_PATH);

    // Strip the Base URL (e.g. /kgs2026/ac/public/) from the URI
    if ($base && strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base));
    }

    $route = trim($uri, '/');
}

/*
|--------------------------------------------------------------------------
| 2. DISPATCH
|--------------------------------------------------------------------------
| The Router (loaded in bootstrap) resolves aliases and renders the page.
*/
$router->dispatch($route);