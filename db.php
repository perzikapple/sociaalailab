<?php
$host = "localhost";
$db   = "sociaalai";
 $user = "root";
 $pass = "";

// $host = "sociju-sociaalailab.db.transip.me";
// $db   = "sociju_sociaalailab";
// $user = "sociju_Sociaalailab";
// $pass = "Techniekcollege12345#";
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
            end_date DATE DEFAULT NULL,
            time TIME DEFAULT NULL,
            time_end TIME DEFAULT NULL,
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
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pageColumns = $pdo->query("SHOW COLUMNS FROM pages")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('sort_order', $pageColumns)) {
        $pdo->exec("ALTER TABLE pages ADD COLUMN sort_order INT DEFAULT 0");
    }
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
    if (!in_array('show_signup_button', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN show_signup_button TINYINT(1) DEFAULT 1");
    }
    if (!in_array('sort_order', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN sort_order INT DEFAULT 0");
    }
    if (!in_array('time_end', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN time_end TIME DEFAULT NULL");
    }
    if (!in_array('end_date', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN end_date DATE DEFAULT NULL");
    }
    if (!in_array('info_link', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN info_link VARCHAR(255) DEFAULT NULL");
    }
    if (!in_array('signup_embed', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN signup_embed TEXT DEFAULT NULL");
    }
    if (!in_array('show_on_homepage', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN show_on_homepage TINYINT(1) DEFAULT 0");
    }
  if (!in_array('event_summary', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN event_summary TEXT DEFAULT NULL");
    }
    if (!in_array('event_gallery', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN event_gallery TEXT DEFAULT NULL");
    }
    if (!in_array('meer_info', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN meer_info TEXT DEFAULT NULL");
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

    $pdo->exec(" 
        CREATE TABLE IF NOT EXISTS accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            wachtwoord VARCHAR(255) NOT NULL,
            first_name VARCHAR(120) DEFAULT NULL,
            last_name VARCHAR(120) DEFAULT NULL,
            admin TINYINT(1) NOT NULL DEFAULT 0,
            role VARCHAR(30) NOT NULL DEFAULT 'viewer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $accountColumns = $pdo->query("SHOW COLUMNS FROM accounts")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('admin', $accountColumns, true)) {
        $pdo->exec("ALTER TABLE accounts ADD COLUMN admin TINYINT(1) NOT NULL DEFAULT 0");
    }
    if (!in_array('role', $accountColumns, true)) {
        $pdo->exec("ALTER TABLE accounts ADD COLUMN role VARCHAR(30) NOT NULL DEFAULT 'viewer'");
    }
    if (!in_array('first_name', $accountColumns, true)) {
        $pdo->exec("ALTER TABLE accounts ADD COLUMN first_name VARCHAR(120) DEFAULT NULL");
    }
    if (!in_array('last_name', $accountColumns, true)) {
        $pdo->exec("ALTER TABLE accounts ADD COLUMN last_name VARCHAR(120) DEFAULT NULL");
    }
    $pdo->exec("UPDATE accounts SET role = 'superadmin' WHERE admin = 1 AND (role IS NULL OR role = '' OR role = 'viewer')");
    $pdo->exec("UPDATE accounts SET admin = 1 WHERE role IN ('superadmin', 'content_manager', 'editor')");
    $pdo->exec("UPDATE accounts SET admin = 0 WHERE role = 'viewer'");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(50) NOT NULL,
            table_name VARCHAR(100) NOT NULL,
            record_id VARCHAR(100) DEFAULT NULL,
            details TEXT DEFAULT NULL,
            performed_by VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inschrijven (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            event_title VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_event_email (event_id, email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

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

    if (!function_exists('seed_page_blocks')) {
        function seed_page_blocks($pdo, $pageKey, $fallbackBlocks) {
            if (empty($fallbackBlocks)) return;
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE page_key = ?');
            $stmt->execute([$pageKey]);
            if ((int)$stmt->fetchColumn() > 0) return;

            $insert = $pdo->prepare('INSERT INTO pages (page_key, title, body, image, meta, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            foreach ($fallbackBlocks as $block) {
                $insert->execute([
                    $pageKey,
                    $block['title'] ?? null,
                    $block['body'] ?? null,
                    $block['image'] ?? null,
                    $block['meta'] ?? null,
                ]);
            }
        }
    }

    if (!function_exists('seed_events')) {
        function seed_events($pdo, $fallbackEvents) {
            if (empty($fallbackEvents)) return;
            $check = $pdo->prepare('SELECT COUNT(*) FROM events WHERE title = ? AND date = ?');
            $insert = $pdo->prepare('INSERT INTO events (title, date, time, description, location, image, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            foreach ($fallbackEvents as $event) {
                $title = $event['title'] ?? null;
                $date = $event['date'] ?? null;
                if (!$title || !$date) continue;
                $check->execute([$title, $date]);
                if ((int)$check->fetchColumn() > 0) continue;
                $insert->execute([
                    $title,
                    $date,
                    $event['time'] ?? null,
                    $event['description'] ?? null,
                    $event['location'] ?? null,
                    $event['image'] ?? null,
                ]);
            }
        }
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
