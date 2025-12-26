<?php
header('Content-Type: text/plain; charset=utf-8');

echo "--- System Info ---\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "OS: " . PHP_OS . "\n";

echo "\n--- Extension Check ---\n";
$extensions = ['curl', 'json', 'pdo_mysql', 'openssl'];
foreach ($extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "Loaded ✅" : "NOT LOADED ❌") . "\n";
}

echo "\n--- cURL Connectivity Test (Google) ---\n";
$ch = curl_init('https://www.google.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// For Synology, sometimes SSL cert is an issue
$res = curl_exec($ch);
if ($res === false) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    echo "cURL Info: " . json_encode(curl_getinfo($ch), JSON_PRETTY_PRINT) . "\n";

    echo "\nTrying without SSL verification (NOT RECOMMENDED for production)...\n";
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res2 = curl_exec($ch);
    if ($res2 !== false) {
        echo "Success WITHOUT SSL verification. This means your server lacks CA certificates.\n";
    } else {
        echo "Still failing: " . curl_error($ch) . "\n";
    }
} else {
    echo "cURL to Google: Success ✅\n";
}
curl_close($ch);

echo "\n--- Database Connection Check ---\n";
try {
    require_once __DIR__ . '/../config/database.php';
    echo "DB Connection: Success ✅ (Host: $host, DB: $dbname)\n";
} catch (Exception $e) {
    echo "DB Connection: FAILED ❌ - " . $e->getMessage() . "\n";
}
