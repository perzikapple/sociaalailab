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

    // Zorg dat de events tabel bestaat
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

    // Zorg dat er een generieke pages tabel is voor andere pagina-inhoud
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
    // Als tabel al bestond zonder kolom: voeg kolom toe als die nog niet bestaat
    $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS location VARCHAR(255) DEFAULT NULL;");
    $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL;");
    $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS updated_by VARCHAR(255) DEFAULT NULL;");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
