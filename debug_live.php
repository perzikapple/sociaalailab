<?php
// Simple debug for live server
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Live Server Debug</h1>";

// 1. Check database connection
echo "<h2>1. Database Connection</h2>";
try {
    require 'db.php';
    echo "✅ Database connected<br>";
    
    // Check password_resets table
    $result = $pdo->query('SHOW TABLES LIKE "password_resets"')->fetch();
    if($result) {
        echo "✅ password_resets table exists<br>";
    } else {
        echo "❌ password_resets table NOT found - trying to create...<br>";
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (email),
                INDEX (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            echo "✅ Table created successfully<br>";
        } catch (Exception $e) {
            echo "❌ Failed to create table: " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Database error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// 2. Check email config
echo "<h2>2. Email Config</h2>";
if(file_exists('email_config.php')) {
    echo "✅ email_config.php file exists<br>";
    require 'email_config.php';
    if(function_exists('sendEmail')) {
        echo "✅ sendEmail() function loaded<br>";
    } else {
        echo "❌ sendEmail() still not found after include<br>";
    }
} else {
    echo "❌ email_config.php NOT FOUND on server<br>";
}

// 3. Check environment variables
echo "<h2>3. Environment Variables & .env File</h2>";

if(file_exists('.env')) {
    echo "✅ .env file exists<br>";
} else {
    echo "❌ .env file NOT FOUND - this is required!<br>";
}

echo "EMAIL_HOST: " . (getenv('EMAIL_HOST') ?: 'NOT SET (using default: smtp-relay.brevo.com)') . "<br>";
echo "EMAIL_USERNAME: " . (getenv('EMAIL_USERNAME') ? '✅ SET' : '❌ NOT SET - REQUIRED') . "<br>";
echo "EMAIL_PASSWORD: " . (getenv('EMAIL_PASSWORD') ? '✅ SET' : '❌ NOT SET - REQUIRED') . "<br>";

// Check if we can at least use fallback PHP mail()
echo "<h2>4. Email Sending Method</h2>";
if(getenv('EMAIL_USERNAME') && getenv('EMAIL_PASSWORD')) {
    echo "✅ Brevo SMTP credentials set - will use Brevo<br>";
} else {
    echo "⚠️ Brevo credentials missing - will use PHP mail() fallback (may not work)<br>";
}

echo "<h2>Summary</h2>";
echo "<strong>Required to fix:</strong><br>";
echo "1. Upload <code>email_config.php</code> to root if missing<br>";
echo "2. Create <code>.env</code> file on server with Brevo credentials<br>";
echo "3. Refresh this page to auto-create password_resets table<br>";
?>
