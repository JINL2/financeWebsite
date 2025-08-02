<?php
ob_start();
session_start();

// 기본 URL 설정
$base_url = '/';

// 이미 로그인한 경우 대시보드로 이동
if (isset($_GET['user_id']) && isset($_GET['company_id'])) {
    header("Location: " . $base_url . "dashboard/");
    exit();
}

// 로그인 페이지로 이동
header("Location: " . $base_url . "login/");
exit();
?>