<?php
/**
 * ROUTER (APPLICATION ENTRY ORCHESTRATOR)
 *
 * Responsibility:
 * - Resolve route → ContentCMSService
 * - Receive normalized page array:
 *      ['meta' => [], 'components' => []]
 * - Pass components into renderer pipeline
 * - Select layout based on meta['layout']
 *
 * Rules:
 * - MUST NOT call ContentCMS directly
 * - MUST NOT read cache directly
 * - MUST NOT implement content logic
 *
 * Router is ONLY responsible for:
 * - routing
 * - layout selection
 * - rendering orchestration
 */

class Router
{
    private array $aliases = [];

    public function alias(string $from, string $to): void
    {
        $this->aliases[trim($from, '/')] = trim($to, '/');
    }

    public function loadAliasesFromSheet(array $rows): void
    {
        $this->applyAliasRows($rows);
    }

    public function dispatch(string $route): void
    {
        $route = trim($route, '/');

        if (isset($this->aliases[$route])) {
            $route = $this->aliases[$route];
        }

        if ($route === '') {
            $route = 'home';
        }

        $route = str_replace(['..', '\\'], '', $route);

        /*
        |--------------------------------------------------------------------------
        | CMS RESOLUTION (ONLY SOURCE OF TRUTH)
        |--------------------------------------------------------------------------
        */
        $cms = ServiceContainer::get('cms');

        if (!$cms) {
            http_response_code(500);
            echo "CMS service not available";
            return;
        }

        $page = $cms->load($route);


        if (!is_array($page)) {
            http_response_code(500);
            echo "Invalid CMS response";
            return;
        }

        $meta = $page['meta'] ?? [];
        $components = $page['components'] ?? [];

        $layout = $meta['layout'] ?? 'default';

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */
        include ROOT_PATH . 'app/layouts/header.php';

        /*
        |--------------------------------------------------------------------------
        | LAYOUT START
        |--------------------------------------------------------------------------
        */
        $layoutStart = ROOT_PATH . "app/layouts/page/{$layout}-start.php";
        if (file_exists($layoutStart)) {
            include $layoutStart;
        }

        /*
        |--------------------------------------------------------------------------
        | COMPONENT PIPELINE
        |--------------------------------------------------------------------------
        */
        ob_start();

        try {
            require_once ROOT_PATH . 'app/components/renderer.php';

            foreach ($components as $component) {
                render_component(
                    $component['type'] ?? '',
                    $component['data'] ?? []
                );
            }

        } catch (Throwable $e) {
            echo '<div class="alert alert-danger">'
                . htmlspecialchars($e->getMessage())
                . '</div>';
        }

        echo ob_get_clean();

        /*
        |--------------------------------------------------------------------------
        | LAYOUT END
        |--------------------------------------------------------------------------
        */
        $layoutEnd = ROOT_PATH . "app/layouts/page/{$layout}-end.php";
        if (file_exists($layoutEnd)) {
            include $layoutEnd;
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */
        include ROOT_PATH . 'app/layouts/footer.php';
    }

    private function applyAliasRows(array $rows): void
    {
        foreach ($rows as $row) {

            $alias = trim($row['alias'] ?? $row[0] ?? '');
            $route = trim($row['route'] ?? $row[1] ?? '');

            if (!$alias || !$route) {
                continue;
            }

            $this->aliases[$alias] = $route;
        }
    }

    public static function getLayout(array $page): string
    {
        return $page['meta']['layout'] ?? 'default';
    }
}