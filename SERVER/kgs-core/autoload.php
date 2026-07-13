<?php
// kgs-core/autoload.php

spl_autoload_register(function ($class) {

    $locations = [

        ROOT_PATH . 'kgs-core/' . $class . '.php',
		ROOT_PATH . 'kgs-core/bootstrap/' . $class . '.php',
        ROOT_PATH . 'kgs-core/cms/' . $class . '.php',
        ROOT_PATH . 'kgs-core/google/' . $class . '.php',
		ROOT_PATH . 'kgs-core/services/' . $class . '.php',
		ROOT_PATH . 'kgs-core/workers/' . $class . '.php',

    ];

    foreach ($locations as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});