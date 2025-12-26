<?php
// 모든 출력을 버퍼링하여 불필요한 경고/공백 차단
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../src/auth.php';

    check_auth_api();

    $userId = $_SESSION['user_id'] ?? 1;

    // 중복된 일정 찾기 및 삭제
    // 1. 구글 연동된 일정이 있으면 우선해서 남김 (Manual 삭제)
    // 2. 둘 다 연동되었거나 둘 다 아니면, 예전 것(ID가 작은 것)을 남김
    $sql = "
        DELETE e1 
        FROM events e1
        INNER JOIN events e2 
        WHERE 
            e1.id != e2.id 
            AND e1.title = e2.title 
            AND e1.start = e2.start 
            AND e1.end = e2.end 
            AND e1.user_id = ? 
            AND e2.user_id = ?
            AND (
                (e1.google_event_id IS NULL AND e2.google_event_id IS NOT NULL) -- e1이 일반, e2가 구글이면 e1 삭제
                OR 
                (
                    -- 둘 다 구글이거나 둘 다 일반이면, 나중에 생성된(e1.id > e2.id) 것을 삭제
                    ((e1.google_event_id IS NOT NULL AND e2.google_event_id IS NOT NULL) OR (e1.google_event_id IS NULL AND e2.google_event_id IS NULL))
                    AND e1.id > e2.id 
                )
            )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $userId]);

    $deletedCount = $stmt->rowCount();

    ob_end_clean();
    echo json_encode(['success' => true, 'count' => $deletedCount, 'message' => "총 {$deletedCount}개의 중복 일정을 정리했습니다."]);
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => '시스템 오류: ' . $e->getMessage()]);
}
