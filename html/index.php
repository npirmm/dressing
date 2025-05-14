<?php
// html/index.php

declare(strict_types=1);

// Start session
// Configure session parameters before session_start()
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax'); // Or 'Strict'
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', '1');
}
session_name('dressing_session'); // Use a custom session name
session_start();


// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
// require_once __DIR__ . '/../config/mail.php'; // If you have it

// Basic error handling for development
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    // TODO: Implement more robust logging for production errors
}

// CSRF Protection basic setup
if (empty($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}


// Simple Router
// -----------------------------------------------------------------------------
$basePath = ''; // If your app is in a subfolder, e.g., /dressing-manager
$requestUri = $_SERVER['REQUEST_URI'];

// Remove query string from URI
if (false !== $pos = strpos($requestUri, '?')) {
    $requestUri = substr($requestUri, 0, $pos);
}
$route = rawurldecode($requestUri);

// Remove base path if any
if ($basePath && strpos($route, $basePath) === 0) {
    $route = substr($route, strlen($basePath));
}
$route = trim($route, '/');
if (empty($route)) {
    $route = 'dashboard'; // Default route
}

// Define controller and action based on route
// Example: 'articles/create' -> ArticleController, createAction
// Example: 'articles' -> ArticleController, indexAction
// Example: 'articles/edit/1' -> ArticleController, editAction(1)

$controllerName = '';
$actionName = 'index'; // Default action
$params = [];

$parts = explode('/', $route);

if (!empty($parts[0])) {
    $controllerName = 'App\\Controllers\\' . ucfirst(strtolower($parts[0])) . 'Controller';
    if (!empty($parts[1])) {
        if (is_numeric($parts[1])) { // e.g., /brands/1 -> BrandsController->show(1)
            $actionName = 'show'; // Or 'edit' or 'delete' depending on HTTP method or further path parts
            $params[] = (int)$parts[1];
            // More sophisticated logic might be needed here for RESTful routes like /brands/1/edit
            if (!empty($parts[2])) { // e.g., /brands/1/edit
                $actionName = strtolower($parts[2]); // so 'edit'
            }
        } else {
            $actionName = strtolower($parts[1]);
            if (!empty($parts[2]) && is_numeric($parts[2])) { // e.g., /articles/edit/1
                 $params[] = (int)$parts[2];
            }
            // Capture further parameters if any: /controller/action/param1/param2
            for ($i = (is_numeric($parts[2]) ? 3 : 2) ; $i < count($parts); $i++) {
                $params[] = $parts[$i];
            }
        }
    }
} else { // Default route, e.g., /
    $controllerName = 'App\\Controllers\\DashboardController';
    $actionName = 'index';
}

// Quick fix for default route /dashboard if no specific controller is given
if ($route === 'dashboard') {
    $controllerName = 'App\\Controllers\\DashboardController';
    $actionName = 'index';
}


// Instantiate controller and call action
// -----------------------------------------------------------------------------
if (class_exists($controllerName)) {
    $controller = new $controllerName();
    if (method_exists($controller, $actionName)) {
        // Call the action method, passing parameters
        // This uses argument unpacking
        try {
            call_user_func_array([$controller, $actionName], $params);
        } catch (TypeError $e) {
            // This can happen if method signature doesn't match params
            error_log("Routing TypeError: " . $e->getMessage() . " for " . $controllerName . "::" . $actionName);
            // Render a 404 or 500 page
            http_response_code(500);
            echo "Error: Method parameters mismatch.";
            if (APP_DEBUG) {
                echo "<pre>" . $e . "</pre>";
            }
        }
    } else {
        http_response_code(404);
        error_log("Action {$actionName} not found in controller {$controllerName}");
        echo "404 Not Found - Action '{$actionName}' does not exist.";
    }
} else {
    http_response_code(404);
    error_log("Controller {$controllerName} not found for route {$route}");
    echo "404 Not Found - Controller for '{$route}' does not exist.";
}

?>