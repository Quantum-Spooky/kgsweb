<?php
/**
 * SYSTEM BOOTSTRAPPER
 * 
 * Responsibility:
 * 1. Define physical paths and prevent double-rendering.
 * 2. Load all configuration (Single Source of Truth: config() function).
 * 3. Initialize core services (Client, CMS, Router).
 * 4. Perform pre-request tasks (Route Aliasing, Cache Repair).
 */
 
/*
|--------------------------------------------------------------------------
| ARCHITECTURAL GUARANTEE:
|--------------------------------------------------------------------------
| Source of truth chain:
| ContentCMS -> ContentCMSService -> CMSCache -> GoogleDriveCache -> Router -> Renderer
| NO OTHER PATH IS VALID.
|--------------------------------------------------------------------------
*/

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

// Set timezone to Central Time (handles CST/CDT automatically)
date_default_timezone_set('America/Chicago');

/**
 * AUTOMATIC SILENT MODE
 * Detects if we are in a state where echoing scripts/logs would be harmful.
 */
if (!defined('KGS_SILENT_MODE')) {
    $isCli    = (php_sapi_name() === 'cli');
    $isAjax   = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    $uri      = $_SERVER['REQUEST_URI'] ?? '';
    $accept   = $_SERVER['HTTP_ACCEPT'] ?? '';
    
    $isRefreshScript = (str_contains($uri, 'refresh-cache.php'));
    $isJsonExpected  = (str_contains($accept, 'application/json'));

    // Silence the system if it's CLI, AJAX, the refresh script, or expecting JSON
    if ($isCli || $isAjax || $isRefreshScript || $isJsonExpected) {
        define('KGS_SILENT_MODE', true);
    }
}


// Global guard to prevent the system from booting twice in one request
if (defined('KGS_ALREADY_RENDERED')) return;
define('KGS_ALREADY_RENDERED', true);

/*
|--------------------------------------------------------------------------
| 1. CORE DEPENDENCIES (Autoloaders)
|--------------------------------------------------------------------------
| This MUST run before any classes are called (like GoogleDriveCache).
*/
require_once ROOT_PATH . 'vendor/autoload.php';
require_once ROOT_PATH . 'kgs-core/autoload.php';

/*
|--------------------------------------------------------------------------
| 2. CONFIGURATION ENGINE (Single Source of Truth)
|--------------------------------------------------------------------------
| This function merges static files, constants, and Google Sheet 
| overrides into one searchable list. This is the heart of the token system.
*/
require_once ROOT_PATH . 'cfg/config.php';

function config(string $key, $default = null)
{
    static $mergedConfig = null;

    if ($mergedConfig === null) {
        global $config; // from config.php
        
        // Start with the hardcoded config array
        $mergedConfig = is_array($config) ? $config : [];

        // Merge in the google.php file if it exists
        $googleFile = ROOT_PATH . 'cfg/google.php';
        if (file_exists($googleFile)) {
            $googleData = include $googleFile;
            if (is_array($googleData)) {
                // Flatten nested arrays for easier access via tokens
                $mergedConfig = array_merge($mergedConfig, $googleData);
                if (isset($googleData['drive'])) $mergedConfig = array_merge($mergedConfig, $googleData['drive']);
                if (isset($googleData['calendar'])) $mergedConfig = array_merge($mergedConfig, $googleData['calendar']);
                if (isset($googleData['facebook'])) $mergedConfig = array_merge($mergedConfig, $googleData['facebook']);
            }
        }

        // Merge in the Google Sheet overrides (The absolute winner)
        $sheetCache = ROOT_PATH . 'kgs-cache/google/config_map.json';
        if (file_exists($sheetCache)) {
            $overrides = json_decode(file_get_contents($sheetCache), true);
            if (is_array($overrides)) {
                $mergedConfig = array_merge($mergedConfig, $overrides);
            }
        }
    }

    // --- RESOLUTION ---
    
    // Check our fat merged array first
    if (isset($mergedConfig[$key])) {
        return $mergedConfig[$key];
    }

    // Fallback: Check PHP Constants (uppercase match)
    $const = strtoupper($key);
    if (defined($const)) {
        return constant($const);
    }

    return $default;
}

/*
|--------------------------------------------------------------------------
| 3. SERVICE INITIALIZATION
|--------------------------------------------------------------------------
*/

// Google API Client
$googleClient = new GoogleDriveClient();
ServiceContainer::set('google_client', $googleClient);

// CMS Content Service
require_once ROOT_PATH . 'kgs-core/services/ContentCMSService.php';
$cmsService = new ContentCMSService();
ServiceContainer::set('cms', $cmsService);

// Application Router
require_once ROOT_PATH . 'kgs-core/Router.php';
$router = new Router();
ServiceContainer::set('router', $router);

/*
|--------------------------------------------------------------------------
| 4. ROUTE ALIAS SYNC
|--------------------------------------------------------------------------
| Pre-processes the URL aliases defined in the Google Sheet.
*/
$aliasSheetId = (string)config('route_aliases_sheet_id', '');

if (!empty($aliasSheetId)) {
    require_once ROOT_PATH . 'kgs-core/services/RouteAliasService.php';
    $aliasService = new RouteAliasService();
    $aliasService->sync($aliasSheetId, $router);
}

/*
|--------------------------------------------------------------------------
| 5. CACHE BUSTING & VERSIONING (Task 3)
|--------------------------------------------------------------------------
| We use the timestamp of the config_map.json as a version number.
| This forces browsers to download fresh CSS/JS whenever the worker runs.
*/
$versionPath = ROOT_PATH . 'kgs-cache/google/config_map.json';
define('KGS_ASSET_VER', file_exists($versionPath) ? filemtime($versionPath) : time());

/*
|--------------------------------------------------------------------------
| 6. SYSTEM HOOKS & HELPERS
|--------------------------------------------------------------------------
*/

/**
 * GLOBAL URL HELPER
 * Ensures links work correctly in subdirectories (like /kgs2026/ac/public/).
 */
function url(?string $path): string {
    if (!$path || empty(trim($path))) return '#';
    
    // 1. Pass through external links or mailto
    if (str_starts_with($path, 'http') || str_starts_with($path, 'mailto') || str_starts_with($path, '#')) {
        return $path;
    }

    // 2. Resolve internal routes
    // Priority: config('base_url') -> constant BASE_URL -> Default /
    $base = config('base_url');
    if (empty($base)) {
        $base = defined('BASE_URL') ? BASE_URL : '/';
    }

    // Ensure base starts and ends with a single slash
    $base = '/' . ltrim($base, '/');
    $base = rtrim($base, '/') . '/';

    // Strip leading slash from path and combine
    return $base . ltrim($path, '/');
}

// ONLY run this if we are not in silent mode AND we are NOT in the middle of building a page.
// This prevents the log from appearing at the very top of the browser source.
if (!defined('KGS_SILENT_MODE')) {
    if (defined('RUN_CACHE_REPAIR') && RUN_CACHE_REPAIR === true) {
        // We only run this logic if the URL is a special 'repair' URL or manual trigger
        if (isset($_GET['repair']) && $_GET['repair'] === 'true') {
             if (class_exists('GoogleDriveCache')) {
                $result = GoogleDriveCache::repairPagesCache();
                console_log('cache_repair_result', $result);
            }
        }
    }
}

// Global View Helper
function view(string $path, array $data = []) {
    $file = ROOT_PATH . 'app/' . ltrim($path, '/') . '.php';
    if (!file_exists($file)) return;
    extract($data);
    include $file;
}

// Javascript Browser Console Logger
function console_log($label, $data = null)
{
    // 1. DONT ECHO IF SILENT MODE IS ACTIVE
    if (defined('KGS_SILENT_MODE')) return;

    // 2. Don't echo if we are in a terminal (CLI)
    if (php_sapi_name() === 'cli') return;

    // 3. Don't echo if the browser is expecting JSON 
    if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        return;
    }

    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "<script>console.log(" . json_encode($label) . ", $json);</script>";
}

// Custom Runtime Enforcer
if (class_exists('RuntimeRules')) {
    RuntimeRules::enforce();
}

// Link list helper -- Returns an array of links from the cached Links tab.
function get_link_group(string $category): array {
    static $allLinks = null;
    if ($allLinks === null) {
        $path = ROOT_PATH . 'kgs-cache/google/links_map.json';
        $allLinks = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    }
    return $allLinks[$category] ?? [];
}

/**
 * Converts a Google Drive File ID into a publicly accessible direct image URL.
 * sz=w1600 provides a high-res version suitable for backgrounds.
 */
function get_drive_url(?string $id, $size = 1600): string {
    if (!$id || str_starts_with($id, '@')) return '';
    // Use the thumbnail proxy which is fast and handles scaling
    return "https://drive.google.com/thumbnail?id=" . trim($id) . "&sz=w" . $size;
}

/**
 * Global Server Cache Purge
 * Attempts to clear PHP OpCache and LiteSpeed/Nginx caches.
 */
function purge_server_cache() {
    // 1. Clear PHP OpCache (Forces server to re-read PHP files)
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    // 2. LiteSpeed Cache Purge (Common on cPanel)
    // Sending this header tells LiteSpeed to purge all cached pages for this site
    if (!headers_sent()) {
        header('X-LiteSpeed-Purge: *');
    }
}

/**
 * Converts Hex color to RGB string (e.g., #015BA7 -> 1, 91, 167)
 * Used for dynamic transparency in CSS.
 */
function hexToRgbList($hex) {
    $hex = str_replace("#", "", $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    return "$r, $g, $b";
}

/**
 * Global Date Extractor for Sorting
 */
function kgs_fl_extract_sort_date(string $name): ?string {
    if (preg_match('/(\d{4})[-_ ](\d{1,2})[-_ ](\d{1,2})/', $name, $m)) return $m[1] . sprintf('%02d', $m[2]) . sprintf('%02d', $m[3]);
    if (preg_match('/\b(\d{4})[-_ ](0[1-9]|1[0-2])\b/', $name, $m)) return $m[1] . sprintf('%02d', $m[2]) . '00';
    $months = "January|February|March|April|May|June|July|August|September|October|November|December|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec";
    if (preg_match("/($months)\s+(\d{1,2})?,?\s*(\d{4})/i", $name, $m)){
        try { return (new DateTime($m[0]))->format('Ymd'); } catch (Exception $e) {}
    }
    if (preg_match('/(\d{4})[-](\d{2,4})/', $name, $m)) return $m[1] . '0000';
    return null;
}

/**
 * Retrieves the automated site menu.
 * Recursively finds a sub-section if a slug is provided.
 */
function get_site_menu(?string $targetSlug = null): array {
    static $menuData = null;
    if ($menuData === null) {
        $path = ROOT_PATH . 'kgs-cache/google/site_menu.json';
        $menuData = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    }
    
    if ($targetSlug === null) return $menuData;

    // Recursive search logic to find a slug anywhere in the tree
    $find = function($items, $slug) use (&$find) {
        foreach ($items as $item) {
            // Is this the one?
            if (($item['slug'] ?? '') === $slug) return $item['items'] ?? [];
            // If not, look inside its children
            if (!empty($item['items'])) {
                $result = $find($item['items'], $slug);
                if ($result) return $result;
            }
        }
        return null;
    };

    return $find($menuData, $targetSlug) ?? [];
}

/**
 * Global Icon Helper (v3.0 - Solid Only)
 */
function get_icon(string $label): string {
    static $map = null;
    if ($map === null) {
        $path = ROOT_PATH . 'kgs-cache/google/icon_map.json';
        $map = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    }

    $labelLower = strtolower(trim($label));
    $baseName = null;

    foreach ($map as $keyword => $icon) {
        if (str_contains($labelLower, (string)$keyword)) {
            $baseName = $icon;
            break;
        }
    }

    // Default to feather if no keyword match
    if (!$baseName) $baseName = 'feather-pointed';
    
    // Handle Brands
    $brands = ['facebook', 'facebook-f', 'twitter', 'instagram', 'tiktok', 'youtube', 'google', 'google-drive'];
    if (in_array($baseName, $brands)) {
        return "fa-brands fa-{$baseName}";
    }

    return "fa-solid fa-{$baseName}";
}