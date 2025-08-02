<?php
/**
 * 재무관리 시스템 - 메인 진입점
 */

// 세션 체크
session_start();

// 이미 로그인한 경우 대시보드로 이동
if (isset($_GET['user_id']) && isset($_GET['company_id'])) {
    header("Location: dashboard/");
    exit();
}

// 로그인 페이지로 이동
header("Location: login/");
exit();
?>