<?php
require 'db.php';

$stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'verantwoord-ai' ORDER BY sort_order ASC, created_at ASC LIMIT 5");
$stmt->execute();
$blocks = $stmt->fetchAll();

echo "=== FLEX STRUCTURE DEBUG ===\n\n";

foreach ($blocks as $i => $block) {
    $meta = json_decode($block['meta'], true);
    $imagePosition = $meta['image_position'] ?? 'normal';
    $hasImage = !empty($block['image']);
    $hasText = !empty($block['title']) || !empty($block['body']);
    
    echo "Block " . ($i + 1) . ": " . htmlspecialchars($block['title']) . "\n";
    echo "  Image Position: $imagePosition\n";
    echo "  Has Image: " . ($hasImage ? 'YES' : 'NO') . "\n";
    echo "  Has Text: " . ($hasText ? 'YES' : 'NO') . "\n";
    
    // Simulate the PHP logic
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
    
    echo "  Style: $sectionStyle\n";
    echo "  Expected: Image(" . ($imagePosition === 'left' ? 'LEFT' : ($imagePosition === 'right' ? 'RIGHT' : 'TOP')) . ") + Text beside=(" . ($flexDir === 'row' ? 'BESIDE' : 'BELOW') . ")\n\n";
}

echo "\n=== CHECKING CSS IN style.css ===\n";
$css = file_get_contents('style.css');
if (strpos($css, '.responsive-flex-left') !== false) {
    echo "✓ responsive-flex-left class found\n";
    
    // Extract the media query
    preg_match('/@media[^{]*\{[^}]*\.responsive-flex-left[^}]*flex-direction[^}]*\}/', $css, $matches);
    if ($matches) {
        echo "✓ Media query found for responsive-flex\n";
        echo "  Content: " . substr($matches[0], 0, 100) . "...\n";
    }
} else {
    echo "✗ responsive-flex-left class NOT found\n";
}
?>
