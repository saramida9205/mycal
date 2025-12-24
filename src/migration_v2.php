<?php
require_once __DIR__ . '/../config/database.php';

try {
    // events 테이블에 category 컬럼 추가
    $pdo->exec("ALTER TABLE events ADD COLUMN category VARCHAR(50) DEFAULT 'general' AFTER color");
    echo "Successfully added 'category' column to 'events' table.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Column 'category' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
