<?php
// html/index.php

// --- DEBUGGING (Remove or comment out for production) ---
//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');
//error_reporting(E_ALL);
// --- END DEBUGGING ---

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
// Ensure CSRF_TOKEN_NAME is defined in config/app.php
if (defined('CSRF_TOKEN_NAME') && empty($_SESSION[CSRF_TOKEN_NAME])) {
    try {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // Handle error if random_bytes fails (highly unlikely)
        error_log('Failed to generate CSRF token: ' . $e->getMessage());
        die('A critical security error occurred. Please try again later.');
    }
}


// Simple Router
// -----------------------------------------------------------------------------
$basePath = ''; // If your app is in a subfolder of the DocumentRoot, e.g., /dressing-manager
$requestUri = $_SERVER['REQUEST_URI'];

// Remove query string from URI
if (false !== $pos = strpos($requestUri, '?')) {
    $requestUri = substr($requestUri, 0, $pos);
}
$route = rawurldecode($requestUri);

// Remove base path if any
if (!empty($basePath) && strpos($route, $basePath) === 0) {
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
$params = []; // Initialize $params as an empty array

$parts = explode('/', $route);

if (!empty($parts[0])) {
    // Controller name, e.g., 'brands' -> 'BrandsController'
    $controllerClassName = ucfirst(strtolower($parts[0]));
    $controllerName = 'App\\Controllers\\' . $controllerClassName . 'Controller';

    if (!empty($parts[1])) {
        // If $parts[1] is numeric, it's likely an ID for a 'show' action by default,
        // or an ID for a specific action if $parts[2] exists.
        // Example: /brands/1 -> BrandsController->show(1)
        // Example: /brands/1/edit -> BrandsController->edit(1)
        if (is_numeric($parts[1])) {
            $params[] = (int)$parts[1]; // The ID
            if (!empty($parts[2])) { // An action on a specific resource ID
                $actionName = strtolower($parts[2]); // e.g., 'edit', 'delete'
            } else {
                $actionName = 'show'; // Default for /entity/ID
            }
        } else {
            // $parts[1] is an action name (e.g., 'create', 'store', 'edit')
            $actionName = strtolower($parts[1]);
            // If $parts[2] exists AND is numeric, it's an ID for this action
            // Example: /brands/edit/1 -> BrandsController->edit(1)
            // Example: /brands/delete/1 (if GET, or POST to this URL)
            if (isset($parts[2]) && is_numeric($parts[2])) {
                $params[] = (int)$parts[2];
            }
            // For actions like 'store' or 'create', $params will remain empty from URL parts, which is correct.
            // Additional non-numeric parameters after action/id are not handled by this simple router.
        }
    }
    // If only $parts[0] is set (e.g., /brands), $actionName remains 'index' and $params is empty.
} else { // Should not happen if default route is 'dashboard'
    $controllerName = 'App\\Controllers\\DashboardController';
    $actionName = 'index';
}

// Override for explicit /dashboard route
if ($route === 'dashboard') {
    $controllerName = 'App\\Controllers\\DashboardController';
    $actionName = 'index';
}


// Instantiate controller and call action
// -----------------------------------------------------------------------------
if (!empty($controllerName) && class_exists($controllerName)) {
    $controller = new $controllerName(); // This is where line 115 is, leading to __construct of BrandsController
    if (method_exists($controller, $actionName)) {
        try {
            // Call the action method, passing parameters
            call_user_func_array([$controller, $actionName], $params);
        } catch (TypeError $e) {
            // This can happen if method signature doesn't match params
            error_log("Routing TypeError: " . $e->getMessage() . " for " . $controllerName . "::" . $actionName . " with params " . json_encode($params));
            http_response_code(500);
            echo "Error: Method parameters mismatch.";
            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo "<pre>Exception details:\n" . $e . "</pre>";
            }
        } catch (Exception $e) { // Catch any other general exceptions from controller actions
            error_log("Controller Action Exception: " . $e->getMessage() . " in " . $controllerName . "::" . $actionName);
            http_response_code(500);
            echo "An unexpected error occurred.";
             if (defined('APP_DEBUG') && APP_DEBUG) {
                echo "<pre>Exception details:\n" . $e . "</pre>";
            }
        }
    } else {
        http_response_code(404);
        error_log("Action '{$actionName}' not found in controller '{$controllerName}' for route '{$route}'");
        echo "404 Not Found - Action '{$actionName}' does not exist in controller '{$controllerClassName}'.";
    }
} else {
    http_response_code(404);
    error_log("Controller '{$controllerName}' not found for route '{$route}'");
    echo "404 Not Found - Controller for route '{$route}' (expected '{$controllerName}') does not exist.";
}

?>