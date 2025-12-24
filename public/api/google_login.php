<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/google_config.php';

check_auth_api(); // 인증 체크

// Google OAuth 2.0 권한 요청 URL 빌드
$auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => GOOGLE_SCOPES,
    'access_type' => 'offline',
    'prompt' => 'consent' // 리프레시 토큰을 받기 위해 동의 창 강제
]);

header('Location: ' . $auth_url);
exit;
