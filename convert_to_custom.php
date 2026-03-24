<?php
require 'db.php';

// Convert items 171 and 172 to custom layout (from card)
echo "Converting card items to custom layout with left/right positioning...\n";

// Item 171: Samen leren -> custom layout, right positioning
$stmt = $pdo->prepare('SELECT id, title, body, image, meta FROM pages WHERE id = 171');
$stmt->execute();
$item = $stmt->fetch();
$meta = json_decode($item['meta'] ?? '{}', true);
$meta['layout'] = 'custom';
$meta['image_position'] = 'right';

$stmt = $pdo->prepare('UPDATE pages SET meta = ? WHERE id = 171');
$stmt->execute([json_encode($meta)]);
echo "✓ Item 171 (Samen leren) -> custom layout, right positioning\n";

// Item 172: Meedoen -> custom layout, left positioning
$stmt = $pdo->prepare('SELECT meta FROM pages WHERE id = 172');
$stmt->execute();
$item = $stmt->fetch();
$meta = json_decode($item['meta'] ?? '{}', true);
$meta['layout'] = 'custom';
$meta['image_position'] = 'left';

$stmt = $pdo->prepare('UPDATE pages SET meta = ? WHERE id = 172');
$stmt->execute([json_encode($meta)]);
echo "✓ Item 172 (Meedoen) -> custom layout, left positioning\n";

// Verify
echo "\nVerifying updates:\n";
$stmt = $pdo->query('SELECT id, title, meta FROM pages WHERE id IN (171, 172)');
foreach ($stmt->fetchAll() as $row) {
    $meta = json_decode($row['meta'], true);
    echo "ID {$row['id']}: Layout={$meta['layout']}, ImagePosition={$meta['image_position']}\n";
}
