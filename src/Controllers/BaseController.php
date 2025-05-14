<?php
// src/Controllers/BaseController.php

namespace App\Controllers;

use App\Utils\Helper;

/**
 * Base Controller
 *
 * Provides common functionality for other controllers.
 */
abstract class BaseController {
    /**
     * Renders a view template with data.
     *
     * @param string $viewPath The path to the view file (e.g., 'brands/index').
     * @param array $data Data to pass to the view.
     * @param string $layout The layout file to use (default: 'layouts/main').
     */
protected function renderView(string $viewPath, array $data = [], string $layout = 'layouts/main'): void {
    // Make data available to the view
    extract($data);

    // Get flash messages to display them in the layout/view
    $flashMessages = Helper::getFlashMessages();

    // DÉBOGAGE
    // echo "BaseController: About to render view: {$viewPath}<br>";
    // var_dump($data);
    // die('Debug in renderView'); // Décommentez pour arrêter ici

    // Output buffering to capture view content
    ob_start();
    require __DIR__ . '/../Views/' . $viewPath . '.php'; // Chemin vers la vue, ex: src/Views/brands/show.php
    $content = ob_get_clean(); // Get the content of the view

    // DÉBOGAGE
    // echo "BaseController: Content captured from view:<br><pre>" . Helper::e($content) . "</pre><br>";
    // die('Debug after content capture'); // Décommentez pour arrêter ici

    // Render the layout, passing the captured content
    require __DIR__ . '/../Views/' . $layout . '.php';
}

    /**
     * Verifies CSRF token for POST requests.
     * If invalid, redirects back or shows an error.
     * It's a good practice to call this at the start of POST handling methods.
     */
    protected function verifyCsrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helper::verifyCsrfToken()) {
                // Handle CSRF failure: log, set flash message, redirect
                Helper::logAction('CSRF_FAILURE', null, null, 'CSRF token validation failed.');
                $_SESSION['flash_messages'] = ['danger' => 'Invalid security token. Please try again.'];
                // Redirect to the previous page or a safe default
                $redirectTo = $_SERVER['HTTP_REFERER'] ?? APP_URL . '/dashboard';
                header('Location: ' . $redirectTo);
                exit;
            }
        }
    }
}