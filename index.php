<?php

/**
 * Redirect to public/index.php
 * This file should be placed in the root directory of your Laravel project
 * if you are not serving directly from the public folder
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// If the request is for the root path, simply redirect to public/index.php
if ($uri === '/' || $uri === '') {
    header('Location: public/');
    exit;
}

// Otherwise, rewrite the request to be handled by public/index.php
// This ensures all Laravel routes work correctly
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';

// Include the public/index.php file
require_once __DIR__ . '/public/index.php'; 