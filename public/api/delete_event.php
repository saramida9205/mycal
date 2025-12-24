<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';
check_auth_api();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !is_numeric($input['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => '유효한 ID가 필요합니다.']);
    exit;
}

$id = $input['id'];
$userId = $_SESSION['user_id'] ?? 1;

try {
    $stmt = $pdo->prepare('DELETE FROM events WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        // The event either didn't exist or didn't belong to the user.
        // For security, we don't reveal which.
        echo json_encode(['success' => false, 'message' => '일정을 삭제할 수 없습니다.']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()]);
}
