<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';
check_auth_api();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['ics_file'])) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$file = $_FILES['ics_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '파일 업로드 중 오류가 발생했습니다.']);
    exit;
}

$content = file_get_contents($file['tmp_name']);
$userId = $_SESSION['user_id'] ?? 1;

// ICS 파싱 로직
function parseIcs($content)
{
    $events = [];
    $vevent_blocks = preg_split('/BEGIN:VEVENT/i', $content);
    array_shift($vevent_blocks); // 첫 번째 블록은 VCALENDAR 헤더이므로 제외

    foreach ($vevent_blocks as $block) {
        $event = [];

        // SUMMARY (제목)
        if (preg_match('/SUMMARY:(.*)$/m', $block, $matches)) {
            $event['title'] = trim($matches[1]);
        }

        // DTSTART (시작 시간)
        if (preg_match('/DTSTART(?:;VALUE=DATE)?:(\d{8}T?\d{0,6}Z?)/m', $block, $matches)) {
            $event['start'] = formatIcsDate($matches[1]);
            $event['allDay'] = (strpos($matches[0], 'VALUE=DATE') !== false || strlen($matches[1]) === 8);
        }

        // DTEND (종료 시간)
        if (preg_match('/DTEND(?:;VALUE=DATE)?:(\d{8}T?\d{0,6}Z?)/m', $block, $matches)) {
            $event['end'] = formatIcsDate($matches[1]);
        } else {
            // 종료 시간이 없는 경우 시작 시간과 동일하게 설정
            $event['end'] = $event['start'] ?? null;
        }

        // DESCRIPTION (설명)
        if (preg_match('/DESCRIPTION:(.*)$/m', $block, $matches)) {
            $event['description'] = trim($matches[1]);
        }

        // RRULE (반복 규칙)
        if (preg_match('/RRULE:(.*)$/m', $block, $matches)) {
            $event['recurrence_rule'] = trim($matches[1]);
        }

        // COLOR (색상 지원)
        if (preg_match('/X-APPLE-CALENDAR-COLOR:(#[0-9a-fA-F]{6})/i', $block, $matches)) {
            $event['color'] = $matches[1];
        } elseif (preg_match('/COLOR:(#[0-9a-fA-F]{6})/i', $block, $matches)) {
            $event['color'] = $matches[1];
        }

        if (!empty($event['title']) && !empty($event['start'])) {
            $events[] = $event;
        }
    }
    return $events;
}

function formatIcsDate($icsDate)
{
    // YYYYMMDD 또는 YYYYMMDDTHHMMSSZ 형식 처리
    $year = substr($icsDate, 0, 4);
    $month = substr($icsDate, 4, 2);
    $day = substr($icsDate, 6, 2);

    if (strlen($icsDate) >= 15) {
        $hour = substr($icsDate, 9, 2);
        $min = substr($icsDate, 11, 2);
        $sec = substr($icsDate, 13, 2);
        return "$year-$month-$day $hour:$min:$sec";
    }
    return "$year-$month-$day 00:00:00";
}

try {
    $parsedEvents = parseIcs($content);
    if (empty($parsedEvents)) {
        echo json_encode(['success' => false, 'message' => '가져올 수 있는 일정이 없습니다.']);
        exit;
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'INSERT INTO events (title, start, end, allDay, completed, color, category, recurrence_rule, description, user_id) VALUES (?, ?, ?, ?, 0, ?, "general", ?, ?, ?)'
    );

    foreach ($parsedEvents as $ev) {
        $stmt->execute([
            $ev['title'],
            $ev['start'],
            $ev['end'],
            $ev['allDay'] ? 1 : 0,
            $ev['color'] ?? '#3498db',
            $ev['recurrence_rule'] ?? null,
            $ev['description'] ?? null,
            $userId
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'count' => count($parsedEvents)]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => '오류 발생: ' . $e->getMessage()]);
}
