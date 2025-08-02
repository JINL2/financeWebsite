<?php
/**
 * Secure Download Handler for CSV Files
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate token
$token = $_GET['token'] ?? '';
if (!$token || !isset($_SESSION['downloads'][$token])) {
    http_response_code(404);
    echo 'Download not found or expired';
    exit;
}

$download_info = $_SESSION['downloads'][$token];

// Check if file exists
if (!file_exists($download_info['file_path'])) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

// Check if download has expired (1 hour)
if (time() - $download_info['created_at'] > 3600) {
    // Clean up expired file
    unlink($download_info['file_path']);
    unset($_SESSION['downloads'][$token]);
    http_response_code(410);
    echo 'Download has expired';
    exit;
}

// Get file info
$file_path = $download_info['file_path'];
$filename = $download_info['filename'];
$file_size = filesize($file_path);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');
header('Pragma: no-cache');

// Clear any previous output
if (ob_get_level()) {
    ob_end_clean();
}

// Output file content with proper buffering
if ($file_size > 0) {
    $handle = fopen($file_path, 'rb');
    if ($handle) {
        while (!feof($handle)) {
            echo fread($handle, 8192); // Read in 8KB chunks
            flush();
        }
        fclose($handle);
    } else {
        readfile($file_path); // Fallback
    }
} else {
    // Empty file
    echo '';
}

// Clean up
unlink($file_path);
unset($_SESSION['downloads'][$token]);

exit;
?>