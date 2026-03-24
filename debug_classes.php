<?php
require 'db.php';

$stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'verantwoord-ai' ORDER BY sort_order ASC LIMIT 6");
$stmt->execute();
$blocks = $stmt->fetchAll();

echo "=== CHECKING SECTION CLASSES ===\n\n";

foreach ($blocks as $i => $block) {
    $meta = json_decode($block['meta'], true);
    $imagePosition = $meta['image_position'] ?? 'normal';
    $hasImage = !empty($block['image']);
    $hasText = !empty($block['title']) || !empty($block['body']);
    
    // Simulate class generation
    $sectionClass = '';
    if ($imagePosition === 'left' && $hasText) {
        $sectionClass = ' responsive-flex-left';
    } elseif ($imagePosition === 'right' && $hasText) {
        $sectionClass = ' responsive-flex-right';
    }
    
    echo "Block " . ($i+1) . ": " . htmlspecialchars(substr($block['title'], 0, 30)) . "...\n";
    echo "  Image Position: $imagePosition\n";
    echo "  Has Text: " . ($hasText ? 'YES' : 'NO') . "\n";
    echo "  Section Classes: 'bg-white shadow-lg p-8 max-w-6xl mx-auto my-12" . $sectionClass . "'\n";
    echo "  Responsive Class? " . (!empty($sectionClass) ? "YES - {$sectionClass}" : "NO") . "\n";
    echo "\n";
}
?>
