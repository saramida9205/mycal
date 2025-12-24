<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/google_config.php';

session_start();

if (!isset($_GET['code'])) {
    die('인증 코드가 없습니다.');
}

$code = $_GET['code'];
$userId = $_SESSION['user_id'] ?? 1;

// 1. 인증 코드를 Access Token으로 교환
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
]));

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

if (isset($data['error'])) {
    die('토킹 교환 오류: ' . $data['error_description']);
}

// 2. 토큰 정보를 데이터베이스에 저장
$accessToken = $data['access_token'];
$refreshToken = $data['refresh_token'] ?? null; // 기존에 연동되어 있으면 안 올 수도 있음
$expiresIn = time() + $data['expires_in'];

try {
    // 기존 토큰이 있는지 확인
    $stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE user_id = ?");
    $stmt->execute([$userId]);
    $exists = $stmt->fetch();

    if ($exists) {
        $sql = "UPDATE user_tokens SET google_access_token = ?, google_token_expires = ?";
        $params = [$accessToken, $expiresIn];
        if ($refreshToken) {
            $sql .= ", google_refresh_token = ?";
            $params[] = $refreshToken;
        }
        $sql .= " WHERE user_id = ?";
        $params[] = $userId;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, google_access_token, google_refresh_token, google_token_expires) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $accessToken, $refreshToken, $expiresIn]);
    }

    echo "<script>alert('구글 캘린더 연동이 완료되었습니다.'); window.location.href = '/';</script>";
} catch (PDOException $e) {
    die('DB 저장 오류: ' . $e->getMessage());
}
