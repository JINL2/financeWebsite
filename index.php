<?php
// Railway 환경 확인
if (getenv('RAILWAY_ENVIRONMENT')) {
    // Railway에서는 JavaScript 리디렉션 사용
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Finance System</title>
        <script>
            // URL 파라미터 확인
            const params = new URLSearchParams(window.location.search);
            if (params.has('user_id') && params.has('company_id')) {
                window.location.href = '/dashboard/';
            } else {
                window.location.href = '/login/';
            }
        </script>
    </head>
    <body>
        <p>Redirecting...</p>
    </body>
    </html>
    <?php
} else {
    // 로컬 환경에서는 PHP 헤더 사용
    session_start();
    if (isset($_GET['user_id']) && isset($_GET['company_id'])) {
        header("Location: dashboard/");
        exit();
    }
    header("Location: login/");
    exit();
}
?>