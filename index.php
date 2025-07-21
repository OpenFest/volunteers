<?php
//Front controller

//import configuration
require_once __DIR__ . '/config.php'; // Configuration file for a database and other settings
require_once __DIR__ . '/helper.php'; // Helper functions for common tasks like dumping variables, etc.

if (defined('DEBUG')) {
	//debug
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

// Define application paths
define('BASE_PATH', '');  //keep empty if the app is in the web server root, or set to '/folder_name' if in a subdirectory
define('ASSETS_DIR', __DIR__ . '/assets/');
define('CORE_DIR', __DIR__ . '/core/');
define('PAGES_DIR', __DIR__ . '/pages/');

// Autoload core classes (a very simple autoloader for this example)
spl_autoload_register(function ($class) {
    $file = CORE_DIR . str_replace('\\', '/', $class) . '.php'; // Adjust for namespaces if you add them later
    if (file_exists($file)) {
        require_once $file;
    }
});

$database = new Database();

// Instantiate and run the application
$app = new App(BASE_PATH, PAGES_DIR, $database);
$app->run();
