<?php
// Report all PHP errors
error_reporting(E_ALL);

// Force errors to be displayed in the browser
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
