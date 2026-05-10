<?php

class Router
{
    private array $routes = [];
    private array $aliases = [];

    /*
    |--------------------------------------------------------------------------
    | OPTIONAL LEGACY FILE ROUTING (NOT USED IN CMS MODE)
    |--------------------------------------------------------------------------
    */
    public function loadFromDirectory(string $baseDir)
    {
        $baseDir = rtrim($baseDir, '/');

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir)
        );

        foreach ($files as $file) {
            if ($file->isDir()) continue;
            if ($file->getExtension() !== 'php') continue;

            $path = $file->getPathname();

            $route = str_replace($baseDir, '', $path);
            $route = str_replace('\\', '/', $route);
            $route = preg_replace('/\.php$/', '', $route);
            $route = trim($route, '/');

            if (substr($route, -5) === '/index') {
                $route = substr($route, 0, -6);
            }

            $this->routes[$route] = $path;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MANUAL ALIASES (CODE-BASED)
    |--------------------------------------------------------------------------
    */
    public function alias(string $from, string $to)
    {
        $this->aliases[trim($from, '/')] = trim($to, '/');
    }

    /*
    |--------------------------------------------------------------------------
    | GOOGLE SHEET ALIASES (CMS-DRIVEN)
    |--------------------------------------------------------------------------
    */
    public function loadAliasesFromSheet(array $rows)
    {
        foreach ($rows as $row) {
            if (empty($row['alias']) || empty($row['route'])) continue;

            $this->aliases[trim($row['alias'], '/')] = trim($row['route'], '/');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DISPATCH (CMS CORE)
    |--------------------------------------------------------------------------
    */
    public function dispatch(string $route)
    {
        $route = trim($route, '/');

        // resolve alias first
        if (isset($this->aliases[$route])) {
            $route = $this->aliases[$route];
        }

        if ($route === '') {
            $route = 'home';
        }

        /*
        |--------------------------------------------------------------------------
        | LOAD CMS PAGE FROM GOOGLE DRIVE
        |--------------------------------------------------------------------------
        */
        require_once ROOT_PATH . 'kgs-core/core/DriveCMS.php';

        $page = DriveCMS::loadPage($route);

        $meta = $page['meta'] ?? [];
        $content = $page['content'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | RENDER LAYOUT
        |--------------------------------------------------------------------------
        */
        include ROOT_PATH . 'includes/layout/header.php';

        echo $content;

        include ROOT_PATH . 'includes/layout/footer.php';
    }
}