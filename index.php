<?php
// Front Controller

// Autoloader
require_once __DIR__ . '/core/autoload.php';

use Core\Router;

$router = new Router();

// Define MVC Routes
$router->add('GET', '/', 'HomeController@index');
$router->add('GET', '/category', 'ProductController@category');
$router->add('GET', '/thiet-ke/{slug}', 'ProductController@detail');

// Auth routes
$router->add('GET', '/login', 'AuthController@loginForm');
$router->add('POST', '/login', 'AuthController@login');
$router->add('GET', '/register', 'AuthController@registerForm');
$router->add('POST', '/register', 'AuthController@register');
$router->add('GET', '/logout', 'AuthController@logout');
$router->add('GET', '/resetpass', 'AuthController@resetpassForm');
$router->add('POST', '/resetpass', 'AuthController@resetpass');
// $router->add('GET', '/admin/dashboard', 'AdminController@dashboard');

// Get URL from .htaccess rewrite
$url = $_GET['url'] ?? '/';

// Dispatch
$router->dispatch($url);