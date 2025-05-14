<?php
// src/Controllers/DashboardController.php

namespace App\Controllers;

use App\Utils\Helper;

class DashboardController extends BaseController {
    /**
     * Displays the main dashboard page.
     */
    public function index(): void {
        $data = [
            'pageTitle' => 'Dashboard',
            'welcomeMessage' => 'Welcome to Dressing Manager!'
        ];
        $this->renderView('dashboard/index', $data);
    }
}