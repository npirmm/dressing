<?php
// config/app.php

define('APP_NAME', 'Dressing Manager');
define('APP_URL', 'https://dressing.n-p.be'); // Change to your local dev URL
define('APP_DEBUG', true); // Set to false in production

define('DEFAULT_TIMEZONE', 'Europe/Brussels');
date_default_timezone_set(DEFAULT_TIMEZONE);

// Base path for image storage (must be writable by web server)
// Relative to public/assets/media/
define('ARTICLE_IMAGE_PATH', 'articles/');
define('EVENT_IMAGE_PATH', 'events/');
define('COLOR_IMAGE_PATH', 'colors/'); // If you store color swatch images

// For session management
define('SESSION_COOKIE_NAME', 'dressing_session');
define('SESSION_LIFETIME', 1800); // 30 minutes
define('SESSION_SECURE_COOKIE', false); // Set to true if using HTTPS
define('SESSION_HTTP_ONLY', true);
define('SESSION_SAMESITE', 'Lax');


// CSRF Token name
define('CSRF_TOKEN_NAME', '_csrf_token');

// Logging User ID for actions when no user is logged in (e.g., system tasks)
// Corresponds to the 'system_logger' user ID in the 'users' table (e.g., 2)
define('SYSTEM_USER_ID', 2);