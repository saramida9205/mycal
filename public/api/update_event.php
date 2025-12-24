<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$data = [];
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if (strpos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

$id = $data['id'] ?? '';
$title = $data['title'] ?? '';
$start = $data['start'] ?? '';
$end = $data['end'] ?? '';

// Handle boolean conversion for different input types
$allDay = isset($data['allDay']) && ($data['allDay'] === 'true' || $data['allDay'] === true) ? 1 : 0;
$completed = isset($data['completed']) && ($data['completed'] === 'true' || $data['completed'] === true) ? 1 : 0;

$color = $data['color'] ?? '#3498db';
$recurrenceRule = $data['recurrence_rule'] ?? null;
$description = $data['description'] ?? null;
$userId = 1; // TODO: Replace with session user ID

// 유효성 검사
if (empty($id) || empty($title) || empty($start) || empty($end)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '필수 필드가 누락되었습니다.']);
    exit;
}

// 데이터베이스에 일정 업데이트
try {
    $stmt = $pdo->prepare(
        'UPDATE events SET title = ?, start = ?, end = ?, allDay = ?, completed = ?, color = ?, recurrence_rule = ?, description = ? WHERE id = ? AND user_id = ?'
    );
    $stmt->execute([$title, $start, $end, $allDay, $completed, $color, $recurrenceRule, $description, $id, $userId]);

    // 파일 업로드 처리 (기존 파일 관리 로직은 필요에 따라 추가)

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()]);
}
