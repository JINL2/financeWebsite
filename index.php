<?php
// 테스트용 index.php
echo "Hello from Railway!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server is working!<br>";

// 파일 목록 확인
echo "<h3>Files in directory:</h3>";
$files = scandir(__DIR__);
foreach($files as $file) {
    echo $file . "<br>";
}

// 원래 코드를 주석처리하고 점진적으로 활성화
/*
session_start();
if (isset($_GET['user_id']) && isset($_GET['company_id'])) {
    header("Location: dashboard/");
    exit();
}
header("Location: login/");
exit();
*/
?>