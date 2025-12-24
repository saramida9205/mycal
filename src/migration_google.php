<?php
require_once __DIR__ . '/../config/database.php';

try {
    // events 테이블에 google_event_id 컬럼 추가
    $pdo->exec("ALTER TABLE events ADD COLUMN google_event_id VARCHAR(255) DEFAULT NULL");
    echo "Successfully added 'google_event_id' column to 'events' table.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Column 'google_event_id' already exists.\n";
    } else {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
}

try {
    // user_tokens 테이블 생성 (구글 인증 정보 저장용)
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_tokens (
        user_id INT PRIMARY KEY,
        google_access_token TEXT,
        google_refresh_token TEXT,
        google_token_expires INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Successfully created 'user_tokens' table.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
