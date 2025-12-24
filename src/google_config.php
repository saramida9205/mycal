<?php
// Google API 설정 정보
require_once __DIR__ . '/google_keys.php';
define('GOOGLE_REDIRECT_URI', 'https://mycal.saramida.co.kr/api/google_callback.php');

// API 범위 설정
$scopes = [
    'https://www.googleapis.com/auth/calendar.events',
    'https://www.googleapis.com/auth/userinfo.email',
    'openid'
];

define('GOOGLE_SCOPES', implode(' ', $scopes));
