<?php
require 'db.php';

// Get blocks from verantwoord-ai - get all and filter for one with left/right position
$stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'verantwoord-ai' ORDER BY sort_order ASC, created_at ASC");
$stmt->execute();
$blocks = $stmt->fetchAll();

// Find first block with left/right positioning
$block = null;
foreach ($blocks as $b) {
    $meta = json_decode($b['meta'], true);
    $pos = $meta['image_position'] ?? 'normal';
    if (in_array($pos, ['left', 'right']) && !empty($b['image'])) {
        $block = $b;
        break;
    }
}

if (!$block) {
    echo "No blocks with left/right positioning found";
    exit;
}
$metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
$hasImage = !empty($block['image']);
$hasText = !empty($block['title']) || !empty($block['body']);
$imagePosition = $metaArr['image_position'] ?? 'normal';
if (!in_array($imagePosition, ['normal', 'left', 'right'], true)) $imagePosition = 'normal';

// Calculate what the section style will be
$flexDir = 'column';
if ($imagePosition === 'left' && $hasText) {
    $flexDir = 'row';
} elseif ($imagePosition === 'right' && $hasText) {
    $flexDir = 'row';
}
$sectionStyle = "display: flex; flex-direction: " . $flexDir . ";";
if ($imagePosition !== 'normal' && $hasText) {
    $sectionStyle .= " gap: 2rem; align-items: flex-start; flex-wrap: nowrap;";
} else {
    $sectionStyle .= " gap: 1.5rem;";
}

echo "=== FIRST BLOCK WITH IMAGE ===\n";
echo "Title: " . htmlspecialchars($block['title']) . "\n";
echo "Image Position: $imagePosition\n";
echo "Section Style: $sectionStyle\n\n";

// Show the actual HTML that would be rendered
if ($imagePosition === 'left' && $hasImage) {
    echo "=== START SECTION ===\n";
    echo "<section style=\"$sectionStyle\">\n\n";
    
    echo "=== IMAGE DIV ===\n";
    $imageStyle = 'flex: 0 0 auto; max-width: 280px; width: 100%;';
    echo "Style: $imageStyle\n";
    echo "<div style=\"$imageStyle\">\n";
    echo "  <img src=\"uploads/" . htmlspecialchars($block['image']) . "\">\n";
    echo "</div>\n\n";
    
    echo "=== TEXT DIV ===\n";
    $textStyle = 'flex: 1 1 auto; min-width: 0;';
    echo "Style: $textStyle\n";
    echo "<div style=\"$textStyle\">\n";
    echo "  <h3>" . htmlspecialchars($block['title']) . "</h3>\n";
    echo "  <div>" . substr(strip_tags($block['body']), 0, 50) . "...</div>\n";
    echo "</div>\n\n";
    
    echo "=== END SECTION ===\n";
}
?>
