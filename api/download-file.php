<?php
/**
 * Secure File Download Handler
 * This script handles secure file downloads from the server
 */

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Get and validate filename parameter
    $filename = $_GET['file'] ?? null;
    
    if (!$filename) {
        throw new Exception('No file specified');
    }
    
    // Security: Only allow alphanumeric, underscore, hyphen, and dot
    if (!preg_match('/^[a-zA-Z0-9\-_\s\.]+$/', $filename)) {
        throw new Exception('Invalid filename format');
    }
    
    // Construct file path
    $downloads_dir = '../downloads';
    $file_path = $downloads_dir . '/' . $filename;
    
    // Security: Prevent directory traversal
    $real_path = realpath($file_path);
    $real_downloads_dir = realpath($downloads_dir);
    
    if (!$real_path || !$real_downloads_dir || strpos($real_path, $real_downloads_dir) !== 0) {
        throw new Exception('Invalid file path');
    }
    
    // Check if file exists
    if (!file_exists($file_path)) {
        throw new Exception('File not found');
    }
    
    // Get file info
    $file_size = filesize($file_path);
    $mime_type = 'text/csv';
    
    // Determine proper filename for download
    $download_filename = $filename;
    if (!pathinfo($filename, PATHINFO_EXTENSION)) {
        $download_filename .= '.csv';
    }
    
    // Set headers for file download
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $download_filename . '"');
    header('Content-Length: ' . $file_size);
    header('Content-Transfer-Encoding: binary');
    
    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Read and output file
    readfile($file_path);
    
    // Optional: Delete file after download (uncomment if you want to clean up)
    // unlink($file_path);
    
    exit();
    
} catch (Exception $e) {
    // Clear any headers that might have been set
    if (!headers_sent()) {
        header('HTTP/1.1 404 Not Found');
        header('Content-Type: text/html');
    }
    
    echo '<html><body><h1>File Download Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
    exit();
}
?>