<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';
check_auth_api();

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="my_calendar.ics"');

$userId = $_SESSION['user_id'] ?? 1;

try {
    $stmt = $pdo->prepare('SELECT title, start, end, description FROM events WHERE user_id = ?');
    $stmt->execute([$userId]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "BEGIN:VCALENDAR\r\n";
    echo "VERSION:2.0\r\n";
    echo "PRODID:-//My Calendar//KR\r\n";
    echo "CALSCALE:GREGORIAN\r\n";
    echo "METHOD:PUBLISH\r\n";
    echo "X-WR-CALNAME:My일정관리\r\n";
    echo "X-WR-TIMEZONE:Asia/Seoul\r\n";

    foreach ($events as $event) {
        $start = date('Ymd\THis', strtotime($event['start']));
        $end = date('Ymd\THis', strtotime($event['end']));
        $created = date('Ymd\THis\Z');
        $uid = uniqid() . '@mycal';

        $summary = str_replace(["\r", "\n"], ' ', $event['title']);
        $description = str_replace(["\r", "\n"], ' ', $event['description'] ?? '');

        echo "BEGIN:VEVENT\r\n";
        echo "UID:{$uid}\r\n";
        echo "DTSTAMP:{$created}\r\n";
        echo "DTSTART:{$start}\r\n";
        echo "DTEND:{$end}\r\n";
        echo "SUMMARY:{$summary}\r\n";
        echo "DESCRIPTION:{$description}\r\n";
        echo "END:VEVENT\r\n";
    }

    echo "END:VCALENDAR\r\n";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Error generating ICS file.";
}
