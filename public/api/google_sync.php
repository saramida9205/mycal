<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/GoogleCalendarService.php';

check_auth_api();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 1;
$service = new GoogleCalendarService($pdo, $userId);

try {
    $googleEvents = $service->fetchEvents();
    if (empty($googleEvents)) {
        echo json_encode(['success' => true, 'message' => '연동된 일정이 없거나 가져올 데이터가 없습니다.']);
        exit;
    }

    $count = 0;
    $pdo->beginTransaction();

    // 가져온 일정을 로컬 DB와 매칭하여 삽입 또는 업데이트
    foreach ($googleEvents as $gEv) {
        // 이미 가져온 일적인지 확인
        $stmt = $pdo->prepare("SELECT id FROM events WHERE google_event_id = ? AND user_id = ?");
        $stmt->execute([$gEv['id'], $userId]);
        $exists = $stmt->fetch();

        $start = isset($gEv['start']['dateTime']) ? $gEv['start']['dateTime'] : ($gEv['start']['date'] . 'T00:00:00');
        $end = isset($gEv['end']['dateTime']) ? $gEv['end']['dateTime'] : ($gEv['end']['date'] . 'T23:59:59');
        $allDay = isset($gEv['start']['date']) ? 1 : 0;

        // 날짜 형식 변환 (FullCalendar/DB 호환)
        $start = date('Y-m-d H:i:s', strtotime($start));
        $end = date('Y-m-d H:i:s', strtotime($end));

        if ($exists) {
            // 업데이트 (제목, 시간 등 변경 시)
            $stmt = $pdo->prepare("UPDATE events SET title = ?, start = ?, end = ?, allDay = ?, description = ? WHERE id = ?");
            $stmt->execute([
                $gEv['summary'] ?? '(제목 없음)',
                $start,
                $end,
                $allDay,
                $gEv['description'] ?? '',
                $exists['id']
            ]);
        } else {
            // 신규 삽입
            $stmt = $pdo->prepare("INSERT INTO events (title, start, end, allDay, color, category, description, google_event_id, user_id) VALUES (?, ?, ?, ?, '#4285F4', 'general', ?, ?, ?)");
            $stmt->execute([
                $gEv['summary'] ?? '(제목 없음)',
                $start,
                $end,
                $allDay,
                $gEv['description'] ?? '',
                $gEv['id'],
                $userId
            ]);
            $count++;
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "{$count}개의 새로운 구글 일전이 동기화되었습니다."]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => '동기화 중 오류 발생: ' . $e->getMessage()]);
}
