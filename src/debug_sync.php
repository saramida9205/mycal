<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/GoogleCalendarService.php';

// Simulate session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// For testing, force userId 1
$userId = 1;

echo "UserId: " . $userId . "\n";

try {
    $service = new GoogleCalendarService($pdo, $userId);
    echo "Checking Token...\n";
    $token = $service->getAccessToken();
    if (!$token) {
        echo "Token not found in user_tokens table.\n";
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_tokens'");
        if ($stmt->rowCount() == 0) {
            echo "Table 'user_tokens' does NOT exist!\n";
        } else {
            echo "Table 'user_tokens' exists, but no entry for userId 1.\n";
            $stmt = $pdo->query("SELECT COUNT(*) FROM user_tokens");
            echo "Total rows in user_tokens: " . $stmt->fetchColumn() . "\n";
        }
    } else {
        echo "Token found. Length: " . strlen($token) . "\n";
        echo "Fetching Events...\n";
        $events = $service->fetchEvents();
        echo "Found " . count($events) . " events.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
