<?php

//Front controller

// Define application paths
define('BASE_PATH', '');  //keep empty if the app is in the web server root, or set to '/folder_name' if in a subdirectory
define('CORE_DIR', __DIR__ . '/core/');
define('PAGES_DIR', __DIR__ . '/pages/');

// Autoload core classes (a very simple autoloader for this example)
spl_autoload_register(function ($class) {
    $file = CORE_DIR . str_replace('\\', '/', $class) . '.php'; // Adjust for namespaces if you add them later
    if (file_exists($file)) {
        require_once $file;
    }
});

// Instantiate and run the application
$app = new App(BASE_PATH, PAGES_DIR);
$app->run();
