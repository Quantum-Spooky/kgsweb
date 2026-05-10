<?php

define('ROOT_PATH', dirname(__DIR__) . '/');

require_once ROOT_PATH . 'cfg/config.php';

function view($path, $data = [])
{
    $file = ROOT_PATH . 'includes/' . $path . '.php';

    if (file_exists($file)) {
        extract($data);
        include $file;
    }
}

function fetch_route_aliases_from_sheet()
{
    return [];
}