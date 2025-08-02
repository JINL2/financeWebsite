<?php
// 에러 표시
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 기본 테스트
echo "<h1>Finance Website</h1>";
echo "<p>PHP is working!</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// 디렉토리 확인
echo "<h3>Directory Contents:</h3>";
echo "<pre>";
print_r(scandir(__DIR__));
echo "</pre>";

// 로그인 폴더 확인
if (file_exists(__DIR__ . '/login/index.php')) {
    echo "<p>✓ Login folder exists</p>";
    echo '<a href="/login/">Go to Login</a>';
} else {
    echo "<p>✗ Login folder NOT found</p>";
}

// 대시보드 폴더 확인
if (file_exists(__DIR__ . '/dashboard/index.php')) {
    echo "<p>✓ Dashboard folder exists</p>";
} else {
    echo "<p>✗ Dashboard folder NOT found</p>";
}
?>