<?php
// Database configuration

// Load from config.ini
$config = parse_ini_file("../../config/config.ini", true);

// Database credentials
define('DB_HOST', $config['database']['DB_HOST'] ?? 'localhost');
define('DB_USER', $config['database']['DB_USER'] ?? 'root');
define('DB_PASSWORD', $config['database']['DB_PASSWORD'] ?? '');
define('DB_NAME', $config['database']['DB_NAME'] ?? 'telegram_manager');

// Create connection
function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// API endpoint
define('API_ENDPOINT', 'http://localhost:' . ($config['api']['API_PORT'] ?? '5000'));

// Security settings
define('SESSION_TIMEOUT', $config['security']['SESSION_TIMEOUT'] ?? 900); // 15 minutes
