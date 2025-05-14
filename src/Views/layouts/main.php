<?php
use App\Utils\Helper;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Helper::e($pageTitle ?? 'Dressing Manager'); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <!-- Add Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>/dashboard"><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (str_contains($_SERVER['REQUEST_URI'], '/dashboard') ? 'active' : ''); ?>" href="<?php echo APP_URL; ?>/dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (str_contains($_SERVER['REQUEST_URI'], '/articles') ? 'active' : ''); ?>" href="<?php echo APP_URL; ?>/articles">Articles</a> <!-- Placeholder -->
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo (str_contains($_SERVER['REQUEST_URI'], '/brands') || str_contains($_SERVER['REQUEST_URI'], '/colors') ? 'active' : ''); ?>" href="#" id="manageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Manage Data
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="manageDropdown">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/brands">Brands</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/colors">Colors</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/materials">Materials</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/categories_types">Categories/Types</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/statuses">Statuses</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/event_types">Event Types</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/storage_locations">Storage Locations</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/suppliers">Suppliers</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/item_users">Item Users</a></li>
                        </ul>
                    </li>
                    <!-- User auth links will go here later -->
                </ul>
            </div>
        </div>
    </nav>

    <main class="container">
        <?php if (!empty($flashMessages)): ?>
            <?php foreach ($flashMessages as $type => $message): ?>
                <div class="alert alert-<?php echo Helper::e($type); ?> alert-dismissible fade show" role="alert">
                    <?php echo Helper::e($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php echo $content; // This is where the specific view content will be injected ?>
    </main>

    <footer class="mt-5 py-3 bg-light text-center">
        <p>© <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/script.js"></script>
</body>
</html>