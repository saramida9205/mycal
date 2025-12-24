<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function check_auth()
{
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $redirect_uri = $protocol . '://' . $host . '/login.php';

        // Prevent infinite redirect loop if already on login.php
        if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
            header('Location: ' . $redirect_uri);
            exit;
        }
    }
}

function check_auth_api()
{
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
        exit;
    }
}
