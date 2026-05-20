<?php
require 'db.php';

echo "=== DEBUG RESET PASSWORD ===\n\n";

// 1. Check table exists
echo "1. Checking password_resets table...\n";
$result = $pdo->query('SHOW TABLES LIKE "password_resets"')->fetch();
if($result) {
    echo "✅ Table EXISTS\n\n";
} else {
    echo "❌ Table NOT found - creating...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (email),
        INDEX (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Table created\n\n";
}

// 2. Check if there are any reset tokens
echo "2. Checking existing reset tokens:\n";
$tokens = $pdo->query("SELECT email, token, expires_at FROM password_resets ORDER BY created_at DESC LIMIT 5")->fetchAll();
if(empty($tokens)) {
    echo "No tokens found\n\n";
} else {
    foreach($tokens as $t) {
        echo "- Email: {$t['email']}, Expires: {$t['expires_at']}\n";
    }
    echo "\n";
}

// 3. Check if localhost URL needs fixing
echo "3. Current server URL detection:\n";
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
echo "Detected: $protocol$host\n";
echo "Email link should use: $protocol$host/reset_password.php?token=TOKEN\n\n";

echo "✅ Debug complete\n";
?>
