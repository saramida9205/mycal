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

    // JSON Input 받기
    $input = json_decode(file_get_contents('php://input'), true);
    $calendarIds = $input['calendar_ids'] ?? ['primary']; // 기본값은 primary

    if (!is_array($calendarIds)) {
        $calendarIds = ['primary'];
    }

    $totalCount = 0;
    $pdo->beginTransaction();

    foreach ($calendarIds as $calId) {
        $googleEvents = $service->fetchEvents($calId);

        if (empty($googleEvents)) continue;

        foreach ($googleEvents as $gEv) {
            $stmt = $pdo->prepare("SELECT id FROM events WHERE google_event_id = ? AND user_id = ?");
            $stmt->execute([$gEv['id'], $userId]);
            $exists = $stmt->fetch();

            $start = isset($gEv['start']['dateTime']) ? $gEv['start']['dateTime'] : ($gEv['start']['date'] . 'T00:00:00');
            // For all-day events, Google gives exclusive end date (next day).
            // We subtract 1 day to make it inclusive for our 'T23:59:59' logic, or we could keep it exact if we treated it as exclusive.
            // But since we append 23:59:59, we must subtract a day to avoid spanning the next day.
            $end = isset($gEv['end']['dateTime'])
                ? $gEv['end']['dateTime']
                : (date('Y-m-d', strtotime($gEv['end']['date'] . ' -1 day')) . 'T23:59:59');
            $allDay = isset($gEv['start']['date']) ? 1 : 0;

            $start = date('Y-m-d H:i:s', strtotime($start));
            $end = date('Y-m-d H:i:s', strtotime($end));

            // Description 처리
            $description = $gEv['description'] ?? '';
            // 캘린더 출처 표시 (옵션)
            // $description .= "\n[from] " . $calId;

            // Recurrence Rule Parsing
            $recurrenceRule = null;
            if (isset($gEv['recurrence']) && is_array($gEv['recurrence'])) {
                foreach ($gEv['recurrence'] as $rrule) {
                    if (strpos($rrule, 'RRULE:') === 0) {
                        if (strpos($rrule, 'FREQ=DAILY') !== false) {
                            $recurrenceRule = 'daily';
                        } elseif (strpos($rrule, 'FREQ=WEEKLY') !== false) {
                            $recurrenceRule = 'weekly';
                        } elseif (strpos($rrule, 'FREQ=MONTHLY') !== false) {
                            $recurrenceRule = 'monthly';
                        } elseif (strpos($rrule, 'FREQ=YEARLY') !== false) {
                            $recurrenceRule = 'yearly';
                        }
                        break;
                    }
                }
            }

            if ($exists) {
                // Update
                $stmt = $pdo->prepare("UPDATE events SET title = ?, start = ?, end = ?, allDay = ?, description = ?, recurrence_rule = ? WHERE id = ?");
                $stmt->execute([
                    $gEv['summary'] ?? '(제목 없음)',
                    $start,
                    $end,
                    $allDay,
                    $description,
                    $recurrenceRule,
                    $exists['id']
                ]);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO events (title, start, end, allDay, color, category, description, google_event_id, user_id, recurrence_rule) VALUES (?, ?, ?, ?, '#4285F4', 'general', ?, ?, ?, ?)");
                $stmt->execute([
                    $gEv['summary'] ?? '(제목 없음)',
                    $start,
                    $end,
                    $allDay,
                    $description,
                    $gEv['id'],
                    $userId,
                    $recurrenceRule
                ]);
                $totalCount++;
            }
        }
    }

    $pdo->commit();
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => "총 {$totalCount}개의 새로운 구글 일정이 동기화되었습니다."]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => '시스템 오류: ' . $e->getMessage()]);
}
