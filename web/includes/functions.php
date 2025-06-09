<?php
/**
 * Helper functions for Telegram Manager
 */

/**
 * Make API request to the Flask backend
 * 
 * @param string $endpoint API endpoint
 * @param string $method HTTP method (GET, POST, etc.)
 * @param array $data Data to send
 * @param bool $auth Whether to include auth token
 * @return array Response data
 */
function api_request($endpoint, $method = 'GET', $data = [], $auth = true) {
    $url = API_ENDPOINT . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set request method
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    // Set headers
    $headers = ['Content-Type: application/json'];
    
    // Include auth token if required
    if ($auth && isset($_SESSION['access_token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['access_token'];
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Set request data
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error, 'code' => $http_code];
    }
    
    curl_close($ch);
    
    // Parse response
    $result = json_decode($response, true);
    
    // Check for JSON parsing error
    if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Invalid JSON response', 'code' => $http_code];
    }
    
    // Add HTTP code to result
    $result['code'] = $http_code;
    
    return $result;
}

/**
 * Get user by ID
 * 
 * @param int $user_id User ID
 * @return array|false User data or false if not found
 */
function get_user_by_id($user_id) {
    $response = api_request('/api/users/me');
    
    if (isset($response['user'])) {
        return $response['user'];
    }
    
    return false;
}

/**
 * Get all accounts
 * 
 * @return array Accounts
 */
function get_accounts() {
    $response = api_request('/api/accounts');
    
    if (isset($response['accounts'])) {
        return $response['accounts'];
    }
    
    return [];
}

/**
 * Get all operations
 * 
 * @return array Operations
 */
function get_operations() {
    $response = api_request('/api/operations');
    
    if (isset($response['operations'])) {
        return $response['operations'];
    }
    
    return [];
}

/**
 * Get operation details
 * 
 * @param int $operation_id Operation ID
 * @return array|false Operation data or false if not found
 */
function get_operation($operation_id) {
    $response = api_request('/api/operations/' . $operation_id);
    
    if (isset($response['operation'])) {
        return [
            'operation' => $response['operation'],
            'logs' => $response['logs'] ?? []
        ];
    }
    
    return false;
}

/**
 * Get dashboard statistics
 * 
 * @return array Statistics
 */
function get_stats() {
    $response = api_request('/api/stats');
    
    if (isset($response['accounts'])) {
        return $response;
    }
    
    return [
        'accounts' => [
            'total' => 0,
            'active' => 0,
            'restricted' => 0,
            'banned' => 0
        ],
        'operations' => [
            'total' => 0,
            'pending' => 0,
            'running' => 0,
            'completed' => 0,
            'failed' => 0,
            'stopped' => 0
        ],
        'recent_operations' => []
    ];
}

/**
 * Format date/time
 * 
 * @param string $datetime Date/time string
 * @param bool $include_time Whether to include time
 * @return string Formatted date/time
 */
function format_datetime($datetime, $include_time = true) {
    if (empty($datetime)) {
        return '-';
    }
    
    $format = $include_time ? 'M d, Y H:i:s' : 'M d, Y';
    return date($format, strtotime($datetime));
}

/**
 * Generate status badge HTML
 * 
 * @param string $status Status string
 * @return string HTML for status badge
 */
function status_badge($status) {
    $class = 'secondary';
    
    switch ($status) {
        case 'active':
            $class = 'success';
            break;
        case 'restricted':
            $class = 'warning';
            break;
        case 'banned':
            $class = 'danger';
            break;
        case 'pending':
            $class = 'info';
            break;
        case 'running':
            $class = 'primary';
            break;
        case 'completed':
            $class = 'success';
            break;
        case 'failed':
            $class = 'danger';
            break;
        case 'stopped':
            $class = 'warning';
            break;
    }
    
    return '<span class="badge bg-' . $class . '">' . ucfirst($status) . '</span>';
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool Whether token is valid
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if string starts with substring
 * 
 * @param string $haystack String to search in
 * @param string $needle Substring to search for
 * @return bool Whether string starts with substring
 */
function starts_with($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Truncate string to specified length
 * 
 * @param string $string String to truncate
 * @param int $length Maximum length
 * @param string $append String to append if truncated
 * @return string Truncated string
 */
function truncate($string, $length = 100, $append = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    
    return substr($string, 0, $length) . $append;
}
