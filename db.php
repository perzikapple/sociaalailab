<?php
$host = "localhost";
$db   = "sociaalai";
$user = "root";
$pass = "";
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            date DATE NOT NULL,
            time TIME DEFAULT NULL,
            description TEXT,
            location VARCHAR(255) DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL,
            updated_by VARCHAR(255) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_key VARCHAR(100) NOT NULL,
            title VARCHAR(255) DEFAULT NULL,
            body TEXT DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            meta JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $columns = $pdo->query("SHOW COLUMNS FROM events")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('location', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN location VARCHAR(255) DEFAULT NULL");
    }
    if (!in_array('updated_at', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL");
    }
    if (!in_array('updated_by', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN updated_by VARCHAR(255) DEFAULT NULL");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('banner1', 'images/banner_website_01.jpg')");
    $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('banner2', 'images/banner_website_02.jpg')");
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(50) NOT NULL,
            table_name VARCHAR(100) NOT NULL,
            record_id VARCHAR(100) DEFAULT NULL,
            details TEXT DEFAULT NULL,
            performed_by VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if (!function_exists('audit_log')) {
        function audit_log($pdo, $action, $table_name, $record_id = null, $details = null, $performed_by = null) {
            try {
                $stmt = $pdo->prepare('INSERT INTO audit_logs (action, table_name, record_id, details, performed_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$action, $table_name, $record_id, $details, $performed_by]);
            } catch (PDOException $e) {
                error_log('Audit log failed: ' . $e->getMessage());
            }
        }
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
