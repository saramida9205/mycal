<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';
check_auth_api();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 1;

try {
    $stmt = $pdo->prepare('DELETE FROM events WHERE user_id = ?');
    $stmt->execute([$userId]);

    echo json_encode(['success' => true, 'message' => '모든 일정이 삭제되었습니다.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()]);
}
