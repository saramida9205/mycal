<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';

// 세션이 없으면 시작 (AJAX 호출 등 대비)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// 로그인 확인
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'] ?? 1;
$backupDir = __DIR__ . '/../../backups/';

// 백업 디렉토리 생성
if (!file_exists($backupDir)) {
    if (!mkdir($backupDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create backup directory']);
        exit;
    }
}

// 오늘 날짜 백업 파일명
$dateStr = date('Ymd');
$filename = "backup_user{$userId}_{$dateStr}.sql";
$filePath = $backupDir . $filename;

// 이미 오늘 백업이 존재하면 패스
if (file_exists($filePath)) {
    echo json_encode(['success' => true, 'message' => 'Backup already exists for today']);
    exit;
}

try {
    // 백업 수행
    $stmt = $pdo->prepare('SELECT * FROM events WHERE user_id = ?');
    $stmt->execute([$userId]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $content = "-- Auto Backup for User {$userId} at " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($events as $row) {
        $keys = array_keys($row);
        $values = array_values($row);
        $escaped_values = array_map(function ($value) use ($pdo) {
            return $value === null ? 'NULL' : $pdo->quote($value);
        }, $values);
        $content .= "INSERT INTO events (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $escaped_values) . ");\n";
    }

    if (file_put_contents($filePath, $content) === false) {
        throw new Exception("Failed to write backup file");
    }

    // 오래된 백업 삭제 (7일 이상 된 파일)
    $files = glob($backupDir . "backup_user{$userId}_*.sql");
    $now = time();
    $deletedCount = 0;

    foreach ($files as $file) {
        // 파일명에서 날짜 추출 (backup_user1_20251224.sql)
        if (preg_match('/_(\d{8})\.sql$/', $file, $matches)) {
            $fileDateStr = $matches[1];
            $fileDate = strtotime($fileDateStr);

            // 7일(초 단위) = 7 * 24 * 60 * 60 = 604800
            if ($now - $fileDate > 604800) {
                unlink($file);
                $deletedCount++;
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Backup created successfully', 'deleted_old_files' => $deletedCount]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
