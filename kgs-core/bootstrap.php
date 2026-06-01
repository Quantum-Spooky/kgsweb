<?php
/*
ARCHITECTURAL GUARANTEE:

Source of truth chain:

ContentCMS
   ↓
ContentCMSService
   ↓
CMSCache
   ↓
GoogleDriveCache (filesystem persistence)
   ↓
Router → Renderer

NO OTHER PATH IS VALID.
*/


if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

if (defined('KGS_ALREADY_RENDERED')) {
    return;
}

define('KGS_ALREADY_RENDERED', true);

/*
|--------------------------------------------------------------------------
| AUTOLOADER
|--------------------------------------------------------------------------
*/
require_once ROOT_PATH . 'vendor/autoload.php';
require_once ROOT_PATH . 'kgs-core/autoload.php';

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/
require_once ROOT_PATH . 'cfg/config.php';

function config(string $key, $default = null)
{
    global $config;

    if (isset($config[$key])) {
        return $config[$key];
    }

    $const = strtoupper($key);

    if (defined($const)) {
        return constant($const);
    }

    return $default;
}

/*
|--------------------------------------------------------------------------
| CORE DEPENDENCIES
|--------------------------------------------------------------------------
*/
	require_once ROOT_PATH . 'kgs-core/config/ConfigRepository.php';
	require_once ROOT_PATH . 'kgs-core/google/GoogleDriveCache.php';
	require_once ROOT_PATH . 'kgs-core/google/GoogleDriveClient.php';
	require_once ROOT_PATH . 'kgs-core/bootstrap/ServiceContainer.php';

/*
|--------------------------------------------------------------------------
| GOOGLE CLIENT
|--------------------------------------------------------------------------
*/
	$googleClient = new GoogleDriveClient();

/*
|--------------------------------------------------------------------------
| SERVICE CONTAINER
|--------------------------------------------------------------------------
*/
	ServiceContainer::set('google_client', $googleClient);
	ServiceContainer::set('config', new ConfigRepository());

/*
|--------------------------------------------------------------------------
| CONFIG OVERRIDES
|--------------------------------------------------------------------------
*/
	$configSheetId = ConfigRepository::get('config_sheet_id');

	if ($configSheetId) {
		$rows = $googleClient->getSheetRows($configSheetId);
		ConfigRepository::overrideFromGoogleSheet($rows);
	}

/*
|--------------------------------------------------------------------------
| CMS SERVICE
|--------------------------------------------------------------------------
*/
	require_once ROOT_PATH . 'kgs-core/services/ContentCMSService.php';
	$cmsService = new ContentCMSService();
	ServiceContainer::set('cms', $cmsService);

/*
|--------------------------------------------------------------------------
| ROUTER
|--------------------------------------------------------------------------
*/
	require_once ROOT_PATH . 'kgs-core/Router.php';
	$router = new Router();
	ServiceContainer::set('router', $router);

/*
|--------------------------------------------------------------------------
| ROUTE ALIASES
|--------------------------------------------------------------------------
*/
	require_once ROOT_PATH . 'kgs-core/services/RouteAliasService.php';

	$aliasService = new RouteAliasService();

	$aliasService->sync(
		ConfigRepository::get('route_aliases_sheet_id'),
		$router
	);
	
/*
|--------------------------------------------------------------------------
| CACHE REPAIR (SAFE BOOTSTRAP HOOK)
|--------------------------------------------------------------------------
*/
	if (defined('RUN_CACHE_REPAIR') && RUN_CACHE_REPAIR === true) {
		$result = GoogleDriveCache::repairPagesCache();
		console_log('cache_repair_result', $result);
	}
	console_log('cache_repair_result', $result);

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
	function view(string $path, array $data = [])
	{
		$file = ROOT_PATH . 'app/' . ltrim($path, '/') . '.php';

		if (!file_exists($file)) {
			return;
		}

		extract($data);
		include $file;
	}

	function console_log($label, $data = null)
	{
		$json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		echo "<script>console.log(" . json_encode($label) . ", $json);</script>";
	}

/*
|--------------------------------------------------------------------------
| OPTIONAL SYSTEM HOOK
|--------------------------------------------------------------------------
*/
	if (class_exists('RuntimeRules')) {
		RuntimeRules::enforce();
	}