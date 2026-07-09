<?php
/**
 * ROUTER (APPLICATION ENTRY ORCHESTRATOR)
 *
 * Responsibility:
 * - Resolve route → ContentCMSService
 * - Receive normalized page array: ['meta' => [], 'components' => []]
 * - Pass components into renderer pipeline
 * - Select layout based on meta['layout']
 *
 * Rules:
 * - MUST NOT call ContentCMS directly
 * - MUST NOT read cache directly
 * - MUST NOT implement content logic
 */

class Router
{
    private array $aliases = [];

    public function alias(string $from, string $to): void { $this->aliases[trim($from, '/')] = trim($to, '/'); }
    public function loadAliasesFromSheet(array $rows): void { $this->applyAliasRows($rows); }

    /**
     * Main Entry Point
     */
    public function dispatch(string $route): void
    {
        // Normalize route
        $route = trim($route, '/') ?: 'home';
        if (isset($this->aliases[$route])) { $route = $this->aliases[$route]; }
        $route = str_replace(['..', '\\'], '', $route);

        /*
        |--------------------------------------------------------------------------
        | 1. COMPONENT RENDERER PRE-LOAD (TASK 9 FIX)
        |--------------------------------------------------------------------------
        | We load the renderer here so that even if the page 404s, the footer
        | (which uses render_component) will have access to the function.
        */
        require_once ROOT_PATH . 'app/components/renderer.php';

        /*
        |--------------------------------------------------------------------------
        | 2. CMS RESOLUTION (ONLY SOURCE OF TRUTH)
        |--------------------------------------------------------------------------
        */
        $cms = ServiceContainer::get('cms');
        $page = $cms ? $cms->load($route) : null;

        // 404 Handler: Stays within the site's layout
        if (!$page || (isset($page['components'][0]['type']) && $page['components'][0]['type'] === 'error-404')) {
            http_response_code(404);
            $this->renderErrorPage("404 - Page Not Found", "The requested page does not exist.");
            return;
        }

        $meta = $page['meta'] ?? [];
        $components = $page['components'] ?? [];
        $layout = $meta['layout'] ?? 'default';
		
		// Ensure these variables are available to included files
        $currentRoute = $route;

        /*
        |--------------------------------------------------------------------------
        | 3. HEADER
        |--------------------------------------------------------------------------
        */
        include ROOT_PATH . 'app/layouts/header.php';

        /*
        |--------------------------------------------------------------------------
        | 4. LAYOUT START (The Wrapper)
        |--------------------------------------------------------------------------
        */
        $layoutStart = ROOT_PATH . "app/layouts/page/{$layout}-start.php";
        if (file_exists($layoutStart)) { include $layoutStart; }

        /*
        |--------------------------------------------------------------------------
        | 5. COMPONENT PIPELINE LOOP
        |--------------------------------------------------------------------------
        */
		ob_start();

		try {
			foreach ($components as $component) {
				$compType = $component['type'] ?? '';
				$data     = $component['data'] ?? [];

				// HYDRATE: Inject global config or constants into the data array
				$data = $this->hydrateComponentData($compType, $data);

				render_component($compType, $data);
			}

		} catch (Throwable $e) {
			echo '<div class="alert alert-danger">Pipeline Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
		}
		echo ob_get_clean();

        /*
        |--------------------------------------------------------------------------
        | 6. LAYOUT END
        |--------------------------------------------------------------------------
        */
        $layoutEnd = ROOT_PATH . "app/layouts/page/{$layout}-end.php";
        if (file_exists($layoutEnd)) { include $layoutEnd; }

        /*
        |--------------------------------------------------------------------------
        | 7. FOOTER
        |--------------------------------------------------------------------------
        */
        include ROOT_PATH . 'app/layouts/footer.php';
    }
	
	/*
	|--------------------------------------------------------------------------
	| RENDER ERROR PAGE
	|--------------------------------------------------------------------------
	*/
	
	private function renderErrorPage($title, $message) {
		$meta = ['title' => $title];
		include ROOT_PATH . 'app/layouts/header.php';
		echo "<div class='container my-5 py-5 text-center'><h1>$title</h1><p>$message</p></div>";
		include ROOT_PATH . 'app/layouts/footer.php';
	}	
	
    /**
     * AUTOMATIC CONFIG HYDRATION
     * 
     * Connects Component fields to Config Keys or Constants via the @ prefix.
     */
	private function hydrateComponentData(string $type, array $data): array
    {
		// Loop through the data provided by the JSON
		foreach ($data as $key => $value) {
			// If a value starts with '@', it's a token
			if (is_string($value) && str_starts_with($value, '@')) {
				$configKey = substr($value, 1); // Remove the '@'
				
				/**
				 * config() is defined in kgs-core/bootstrap.php
				 * It handles both $config['key'] and defined('CONSTANT')
				 */
				$data[$key] = config($configKey);
			}
		}
		
		return $data;
	}

	private function applyAliasRows(array $rows): void {
		foreach ($rows as $row) {
			$alias = trim($row['alias'] ?? $row[0] ?? '');
			$route = trim($row['route'] ?? $row[1] ?? '');
			if ($alias && $route) $this->aliases[$alias] = $route;
		}
	}

    public static function getLayout(array $page): string
    {
        return $page['meta']['layout'] ?? 'default';
    }
}