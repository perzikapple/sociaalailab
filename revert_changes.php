<?php
require 'db.php';

// Revert items 171 and 172 back to card layout
echo "Reverting items back to card layout...\n";

// Item 171: Samen leren -> back to card layout
$stmt = $pdo->prepare('UPDATE pages SET meta = ? WHERE id = 171');
$meta = json_encode(['layout' => 'card', 'image_position' => 'normal', 'image_width_px' => 200]);
$stmt->execute([$meta]);
echo "✓ Item 171 (Samen leren) reverted to card layout\n";

// Item 172: Meedoen -> back to card layout
$stmt = $pdo->prepare('UPDATE pages SET meta = ? WHERE id = 172');
$meta = json_encode(['layout' => 'card', 'image_position' => 'normal', 'image_width_px' => 200]);
$stmt->execute([$meta]);
echo "✓ Item 172 (Meedoen) reverted to card layout\n";

// Verify
echo "\nVerifying reverts:\n";
$stmt = $pdo->query('SELECT id, title, meta FROM pages WHERE id IN (171, 172)');
foreach ($stmt->fetchAll() as $row) {
    $meta = json_decode($row['meta'], true);
    echo "ID {$row['id']}: Layout={$meta['layout']}\n";
}
