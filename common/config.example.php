<?php
/**
 * 재무관리 시스템 설정 파일 예시
 * 
 * 이 파일을 config.php로 복사하고 실제 값으로 수정하세요.
 * 또는 환경변수를 설정하여 사용하세요. (권장)
 */

// Supabase 설정
define('SUPABASE_URL', 'https://your-project-id.supabase.co');
define('SUPABASE_ANON_KEY', 'your-supabase-anonymous-key-here');
define('SUPABASE_SERVICE_KEY', 'your-supabase-service-key-here');

// 페이지네이션 설정
define('DEFAULT_LIMIT', 100);
define('MAX_LIMIT', 1000);

// 시스템 설정
define('SYSTEM_NAME', '재무관리 시스템');
define('DEFAULT_TIMEZONE', 'Asia/Seoul');

// 날짜 형식
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DATE_FORMAT_KR', 'Y년 m월 d일');

// 에러 리포팅 (개발환경)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 타임존 설정
date_default_timezone_set(DEFAULT_TIMEZONE);

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 테스트용 사용자 정보
define('TEST_USER_ID', 'your-test-user-uuid');
define('TEST_MODE', false);
