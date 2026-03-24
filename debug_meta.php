<?php
require_once 'db.php';

// Query all pages with their meta
$stmt = $pdo->query("SELECT id, page_key, title, image, meta FROM pages WHERE meta IS NOT NULL ORDER BY id DESC LIMIT 20");
$pages = $stmt->fetchAll();

echo "<pre>";
foreach ($pages as $page) {
    echo "ID: {$page['id']} | Page: {$page['page_key']} | Title: {$page['title']} | Image: {$page['image']}\n";
    echo "Meta: {$page['meta']}\n";
    if ($page['meta']) {
        $meta = json_decode($page['meta'], true);
        echo "Decoded meta:\n";
        print_r($meta);
    }
    echo "---\n\n";
}
?>
