<?php
require 'db.php';

// Find items on the index page with image and custom layout
$stmt = $pdo->query("SELECT id, title, image, meta FROM pages WHERE page_key = 'index' AND image IS NOT NULL AND image != '' ORDER BY id DESC");
$items = $stmt->fetchAll();

echo "Index items with images:\n";
foreach ($items as $item) {
    $meta = json_decode($item['meta'] ?? '{}', true);
    $layout = $meta['layout'] ?? 'custom';
    echo "ID {$item['id']}: {$item['title']} | Layout: {$layout} | Image: {$item['image']}\n";
}

// Find a custom block item (layout != 'card', 'welcome', 'info')
foreach ($items as $item) {
    $meta = json_decode($item['meta'] ?? '{}', true);
    $layout = $meta['layout'] ?? 'custom';
    
    if (!in_array($layout, ['card', 'welcome', 'info', 'contact'])) {
        $blockId = $item['id'];
        echo "\nTesting with ID {$blockId}: {$item['title']}\n";
        
        // Update to have image_position 'left'
        $meta['image_position'] = 'left';
        
        $stmt = $pdo->prepare('UPDATE pages SET meta = ? WHERE id = ?');
        $stmt->execute([json_encode($meta), $blockId]);
        echo "✓ Updated to image_position=left\n";
        
        // Verify
        $stmt = $pdo->prepare('SELECT meta FROM pages WHERE id = ?');
        $stmt->execute([$blockId]);
        $updated = $stmt->fetch();
        echo "Verified: " . $updated['meta'] . "\n";
        break;
    }
}


