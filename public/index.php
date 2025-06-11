<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';

use Core\Router;
use Core\Request;

$request = new Request();
$router = new Router($request);

$router->get('/', 'Controllers\\HomeController@index');
$router->get('/view', 'Controllers\\HomeController@view');
$router->get('/view404', 'Controllers\\HomeController@view404');

$router->dispatch();