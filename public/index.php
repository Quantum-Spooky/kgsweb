<?php

require_once dirname(__DIR__) . '/kgs-core/bootstrap.php';
require_once ROOT_PATH . 'kgs-core/core/Router.php';

$router = new Router();

/*
|--------------------------------------------------------------------------
| AUTO LOAD FILES
|--------------------------------------------------------------------------
*/
$router->loadFromDirectory(ROOT_PATH . 'includes/pages');

/*
|--------------------------------------------------------------------------
| CMS-DRIVEN ALIASES
|--------------------------------------------------------------------------
*/

$sheetId = config_value('route_aliases_sheet_id');

$aliases = fetch_route_aliases_from_sheet($sheetId);

$router->loadAliasesFromSheet($aliases);

/*
|--------------------------------------------------------------------------
| DISPATCH
|--------------------------------------------------------------------------
*/

$route = $_GET['route'] ?? '';
$router->dispatch($route);