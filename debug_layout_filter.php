<?php
require 'db.php';

echo "=== DEBUG: verantwoord-ai.php blokken ===\n\n";

$stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'verantwoord-ai' ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC");
$stmt->execute();
$allBlocks = $stmt->fetchAll();

echo "TOTAAL blokken in database: " . count($allBlocks) . "\n\n";

$pageBlocks = [];
foreach ($allBlocks as $block) {
    $metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
    $layout = $metaArr['layout'] ?? 'custom';
    $imagePos = $metaArr['image_position'] ?? 'normal';
    
    echo "ID {$block['id']}: " . htmlspecialchars($block['title']) . "\n";
    echo "  Layout: '$layout'\n";
    echo "  Image Position: '$imagePos'\n";
    echo "  Has Image: " . (!empty($block['image']) ? "YES" : "NO") . "\n";
    
    if ($layout === 'custom' || !in_array($layout, ['welcome', 'card', 'info', 'contact'], true)) {
        echo "  ✓ WILL BE SHOWN\n";
        $pageBlocks[] = $block;
    } else {
        echo "  ✗ FILTERED OUT\n";
    }
    echo "\n";
}

echo "\n=== BLOKKEN NA FILTER: " . count($pageBlocks) . " ===\n\n";

// Nu check wat de flex-direction zou zijn
foreach ($pageBlocks as $block) {
    $metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
    $imagePosition = $metaArr['image_position'] ?? 'normal';
    $hasImage = !empty($block['image']);
    $hasText = !empty($block['title']) || !empty($block['body']);
    
    $flexDir = 'column';
    $flexWrap = 'nowrap';
    if ($imagePosition === 'left' && $hasText) {
        $flexDir = 'row';
        $flexWrap = 'nowrap';
    } elseif ($imagePosition === 'right' && $hasText) {
        $flexDir = 'row';
        $flexWrap = 'nowrap';
    }
    
    $sectionStyle = "display: flex; flex-direction: " . $flexDir . "; flex-wrap: " . $flexWrap . ";";
    if ($imagePosition !== 'normal' && $hasText) {
        $sectionStyle .= " gap: 2rem; align-items: flex-start;";
    } else {
        $sectionStyle .= " gap: 1.5rem;";
    }
    
    echo "ID {$block['id']}: " . htmlspecialchars($block['title']) . "\n";
    echo "  Position: $imagePosition | Image: " . ($hasImage ? "YES" : "NO") . " | Text: " . ($hasText ? "YES" : "NO") . "\n";
    echo "  FlexDir: $flexDir | Style: $sectionStyle\n";
    echo "  Expected Layout: ";
    if ($imagePosition === 'left' && $hasImage && $hasText) {
        echo "🖼️ LEFT | TEXT RIGHT (naast elkaar)\n";
    } elseif ($imagePosition === 'right' && $hasImage && $hasText) {
        echo "TEXT LEFT | 🖼️ RIGHT (naast elkaar)\n";
    } else {
        echo "NORMAL (foto boven/alleen)\n";
    }
    echo "\n";
}
?>
