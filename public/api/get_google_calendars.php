<?php
// 모든 출력을 버퍼링하여 불필요한 경고/공백 차단
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../src/auth.php';
    require_once __DIR__ . '/../../src/GoogleCalendarService.php';

    check_auth_api();

    $userId = $_SESSION['user_id'] ?? 1;
    $service = new GoogleCalendarService($pdo, $userId);

    $token = $service->getAccessToken();
    if (!$token) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => "구글 계정이 연결되어 있지 않습니다."]);
        exit;
    }

    $calendars = $service->getCalendarList();

    // 필요한 정보만 추출
    $result = [];
    foreach ($calendars as $cal) {
        $result[] = [
            'id' => $cal['id'],
            'summary' => $cal['summary'],
            'backgroundColor' => $cal['backgroundColor'] ?? '#4285F4',
            'primary' => $cal['primary'] ?? false
        ];
    }

    ob_end_clean();
    echo json_encode(['success' => true, 'calendars' => $result]);
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => '시스템 오류: ' . $e->getMessage()]);
}
