<?php
/**
 * 재무관리 시스템 - 메인 진입점
 */

// 세션 체크
session_start();

// 이미 로그인한 경우 대시보드로 이동
if (isset($_GET['user_id']) && isset($_GET['company_id'])) {
    header('Location: dashboard/?user_id=' . $_GET['user_id'] . '&company_id=' . $_GET['company_id']);
    exit;
}

// 세션에 저장된 정보가 있으면 대시보드로 이동
if (isset($_SESSION['user_id']) && isset($_SESSION['company_id'])) {
    header('Location: dashboard/?user_id=' . $_SESSION['user_id'] . '&company_id=' . $_SESSION['company_id']);
    exit;
}

// 로그인 페이지로 이동
header('Location: login/');
exit;
