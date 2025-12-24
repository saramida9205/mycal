<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';
check_auth_api();

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="mycal_backup_' . date('Ymd_His') . '.sql"');

$userId = $_SESSION['user_id'] ?? 1;

try {
    // events 테이블 백업 (사용자 데이터만)
    $stmt = $pdo->prepare('SELECT * FROM events WHERE user_id = ?');
    $stmt->execute([$userId]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "-- My일정관리 데이터베이스 백업\n";
    echo "-- 생성일시: " . date('Y-m-d H:i:s') . "\n";
    echo "-- 사용자 ID: " . $userId . "\n\n";

    if (empty($events)) {
        echo "-- 백업할 데이터가 없습니다.\n";
    } else {
        foreach ($events as $row) {
            $keys = array_keys($row);
            $values = array_values($row);

            // 값을 SQL 형식으로 이스케이프
            $escaped_values = array_map(function ($value) use ($pdo) {
                if ($value === null) return 'NULL';
                return $pdo->quote($value);
            }, $values);

            echo "INSERT INTO events (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $escaped_values) . ");\n";
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo "-- 백업 중 오류 발생: " . $e->getMessage() . "\n";
}
