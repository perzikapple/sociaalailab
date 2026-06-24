<?php
// Simple debug for live server
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Live Server Debug - Detailed</h1>";

echo "<h2>0. File Check</h2>";
if(file_exists('email_config.php')) {
    echo "✅ email_config.php EXISTS<br>";
} else {
    echo "❌ email_config.php NOT FOUND<br>";
}

if(file_exists('.env')) {
    echo "✅ .env file EXISTS<br>";
} else {
    echo "❌ .env file NOT FOUND<br>";
}

// 1. Check database connection
echo "<h2>1. Database Connection</h2>";
try {
    require 'db.php';
    echo "✅ Database connected<br>";
    
    // Try to create password_resets table
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (email),
        INDEX (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    $result = $pdo->query('SHOW TABLES LIKE "password_resets"')->fetch();
    if($result) {
        echo "✅ password_resets table EXISTS<br>";
    } else {
        echo "❌ password_resets table NOT created<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// 2. Try loading email config
echo "<h2>2. Email Config Loading</h2>";
try {
    if(file_exists('email_config.php')) {
        require 'email_config.php';
        if(function_exists('sendEmail')) {
            echo "✅ sendEmail() function loaded successfully<br>";
        } else {
            echo "❌ sendEmail() function NOT found after loading email_config.php<br>";
        }
    } else {
        echo "❌ email_config.php file does not exist - cannot load<br>";
    }
} catch (Exception $e) {
    echo "❌ Error loading email_config.php: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// 3. Check environment variables
echo "<h2>3. Environment Variables</h2>";
echo "EMAIL_HOST: " . (getenv('EMAIL_HOST') ?: 'NOT SET') . "<br>";
echo "EMAIL_USERNAME: " . (getenv('EMAIL_USERNAME') ? '✅ SET (' . substr(getenv('EMAIL_USERNAME'), 0, 5) . '...)' : '❌ NOT SET') . "<br>";
echo "EMAIL_PASSWORD: " . (getenv('EMAIL_PASSWORD') ? '✅ SET' : '❌ NOT SET') . "<br>";

echo "<h2>Summary</h2>";
echo "<strong>If email_config.php says NOT FOUND:</strong> You must upload it to live server root!<br>";
echo "<strong>If .env says NOT FOUND:</strong> You must create and upload .env to live server root!<br>";
?>

