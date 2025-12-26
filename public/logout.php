<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        // 로그아웃 과정에서의 에러는 무시하거나 로깅
        error_log("Failed to clear Google tokens on logout: " . $e->getMessage());
    }
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();
header('Location: /login.php');
exit;
