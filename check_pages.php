<?php
require 'db.php';

echo "=== Checking VERANTWOORD-AI page ===\n";
$stmt = $pdo->query("SELECT id, title, image, meta FROM pages WHERE page_key = 'verantwoord-ai' ORDER BY id");
$items = $stmt->fetchAll();

foreach ($items as $item) {
    $meta = json_decode($item['meta'] ?? '{}', true);
    $layout = $meta['layout'] ?? 'custom';
    $imagePos = $meta['image_position'] ?? 'normal';
    $hasImage = $item['image'] ? 'YES' : 'NO';
    echo "ID {$item['id']}: {$item['title']} | Layout: $layout | ImagePos: $imagePos | HasImage: $hasImage | Image: {$item['image']}\n";
}

