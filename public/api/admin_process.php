<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';

// 세션 시작 및 인증 체크
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => ''];

// 관리자 권한 체크 (간단하게 username이 'admin'인 경우)
if (!isset($_SESSION['authenticated']) || $_SESSION['username'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    if ($action === 'list_users') {
        // 사용자 목록 및 일정 수 조회
        $sql = "SELECT u.id, u.username, u.created_at, COUNT(e.id) as event_count 
                FROM users u 
                LEFT JOIN events e ON u.id = e.user_id 
                GROUP BY u.id 
                ORDER BY u.id ASC";
        $stmt = $pdo->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['users'] = $users;
    } elseif ($action === 'change_password') {
        $targetUserId = $_POST['user_id'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (!$targetUserId || !$newPassword) {
            throw new Exception("필수 정보가 누락되었습니다.");
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $targetUserId]);

        $response['success'] = true;
        $response['message'] = "비밀번호가 변경되었습니다.";
    } elseif ($action === 'clear_events') {
        $targetUserId = $_POST['user_id'] ?? '';

        if (!$targetUserId) {
            throw new Exception("사용자 ID가 누락되었습니다.");
        }

        $stmt = $pdo->prepare("DELETE FROM events WHERE user_id = ?");
        $stmt->execute([$targetUserId]);

        $response['success'] = true;
        $response['message'] = "모든 일정이 삭제되었습니다.";
    } elseif ($action === 'delete_user') {
        $targetUserId = $_POST['user_id'] ?? '';

        if (!$targetUserId) {
            throw new Exception("사용자 ID가 누락되었습니다.");
        }

        // admin 계정은 삭제 불가
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$targetUserId]);
        $user = $stmt->fetch();

        if ($user && $user['username'] === 'admin') {
            throw new Exception("관리자 계정은 삭제할 수 없습니다.");
        }

        $pdo->beginTransaction();

        // 관련 데이터 삭제
        $pdo->prepare("DELETE FROM events WHERE user_id = ?")->execute([$targetUserId]);
        $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?")->execute([$targetUserId]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetUserId]);

        $pdo->commit();

        $response['success'] = true;
        $response['message'] = "사용자 계정이 삭제되었습니다.";
    } else {
        throw new Exception("잘못된 요청입니다.");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
