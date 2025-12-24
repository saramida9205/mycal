<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// 데이터 처리
$title = $_POST['title'] ?? '';
$start = $_POST['start'] ?? '';
$end = $_POST['end'] ?? '';
$allDay = isset($_POST['allDay']) && $_POST['allDay'] === 'true' ? 1 : 0;
$completed = isset($_POST['completed']) && $_POST['completed'] === 'true' ? 1 : 0;
$color = $_POST['color'] ?? '#3498db';
$recurrenceRule = $_POST['recurrence_rule'] ?? null;
$description = $_POST['description'] ?? null;

// 유효성 검사
if (empty($title) || empty($start) || empty($end)) {
    echo json_encode(['success' => false, 'message' => '필수 필드가 누락되었습니다.']);
    exit;
}

// 데이터베이스에 일정 추가
try {
    $userId = 1; // TODO: Replace with session user ID

    $stmt = $pdo->prepare(
        'INSERT INTO events (title, start, end, allDay, completed, color, recurrence_rule, description, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$title, $start, $end, $allDay, $completed, $color, $recurrenceRule, $description, $userId]);
    $eventId = $pdo->lastInsertId();

    // 파일 업로드 처리
    $uploadDir = __DIR__ . '/../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $attachments = [];
    if (isset($_FILES['attachments'])) {
        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
            $fileName = basename($_FILES['attachments']['name'][$key]);
            $filePath = $uploadDir . $fileName;
            if (move_uploaded_file($tmpName, $filePath)) {
                $attachments[] = $fileName;
            }
        }
    }

    // 첨부 파일 정보를 별도의 테이블에 저장 (예: event_attachments)
    if (!empty($attachments)) {
        $stmt = $pdo->prepare('INSERT INTO event_attachments (event_id, file_name) VALUES (?, ?)');
        foreach ($attachments as $fileName) {
            $stmt->execute([$eventId, $fileName]);
        }
    }

    echo json_encode(['success' => true, 'id' => $eventId]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()]);
}
