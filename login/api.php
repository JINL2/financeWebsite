<?php
/**
 * Login API - Financial Management System  
 * Handles user authentication using Supabase Auth (Environment Variables)
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

require_once '../common/db.php';
require_once '../common/auth.php';

// Supabase configuration from environment variables or config
if (!defined('SUPABASE_URL')) {
    require_once '../common/config.php';
}

try {
    // Get POST data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email and password are required'
        ]);
        exit;
    }

    // Email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid email address'
        ]);
        exit;
    }

    // Authenticate using Supabase Auth
    $authResponse = authenticateWithSupabaseAuth($email, $password);
    
    if (!$authResponse['success']) {
        echo json_encode([
            'success' => false,
            'message' => $authResponse['message']
        ]);
        exit;
    }
    
    $authUser = $authResponse['user'];
    $supabaseUserId = $authUser['id'];

    // Get user from our users table using the Supabase Auth user ID
    $db = new SupabaseDB();
    
    // First, try to find user by email (for backward compatibility)
    $users = $db->query('users', [
        'email' => 'eq.' . $email,
        'is_deleted' => 'eq.false',
        'select' => 'user_id,email,first_name,last_name'
    ]);
    
    if (empty($users)) {
        // If no user found in our users table, but auth succeeded, create a link
        echo json_encode([
            'success' => false,
            'message' => 'Account found but not linked to company. Please contact support.',
            'auth_user_id' => $supabaseUserId
        ]);
        exit;
    }
    
    $user = $users[0];
    
    // Get user's companies
    $companies = getUserCompanies($user['user_id']);
    
    if (empty($companies)) {
        echo json_encode([
            'success' => false,
            'message' => 'No companies associated with your account. Please contact support.'
        ]);
        exit;
    }

    // Use the first company as default
    $defaultCompany = $companies[0];

    echo json_encode([
        'success' => true,
        'user_id' => $user['user_id'],
        'company_id' => $defaultCompany['company_id'],
        'user_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
        'email' => $user['email'],
        'companies' => $companies,
        'auth_user_id' => $supabaseUserId,
        'message' => 'Login successful'
    ]);

} catch (Exception $e) {
    error_log('Login API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}

/**
 * Authenticate user with Supabase Auth
 */
function authenticateWithSupabaseAuth($email, $password) {
    $authUrl = SUPABASE_URL . '/auth/v1/token?grant_type=password';
    
    $postData = json_encode([
        'email' => $email,
        'password' => $password
    ]);
    
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_ANON_KEY
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $authUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $authData = json_decode($response, true);
        return [
            'success' => true,
            'user' => $authData['user'],
            'session' => $authData
        ];
    } else {
        $errorData = json_decode($response, true);
        return [
            'success' => false,
            'message' => $errorData['error_description'] ?? 'Authentication failed'
        ];
    }
}

?>
