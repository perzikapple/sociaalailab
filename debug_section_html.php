<?php
require 'db.php';
require 'helpers.php';

$stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'verantwoord-ai' ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC");
$stmt->execute();
$allBlocks = $stmt->fetchAll();

// Find first block with left/right positioning and image
$block = null;
foreach ($allBlocks as $b) {
    $meta = json_decode($b['meta'], true);
    $pos = $meta['image_position'] ?? 'normal';
    if (in_array($pos, ['left', 'right']) && !empty($b['image'])) {
        $block = $b;
        break;
    }
}

if (!$block) {
    echo "No block with left/right found";
    exit;
}

$metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
$hasImage = !empty($block['image']);
$hasText = !empty($block['title']) || !empty($block['body']);
$imagePosition = $metaArr['image_position'] ?? 'normal';
if (!in_array($imagePosition, ['normal', 'left', 'right'], true)) $imagePosition = 'normal';
$greenText = trim((string)($metaArr['green_text'] ?? ($metaArr['green_heading'] ?? '')));
$greenTextPosition = $metaArr['green_text_position'] ?? 'above';
if (!in_array($greenTextPosition, ['above', 'below'], true)) $greenTextPosition = 'above';

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

echo "=== BLOCK DEBUG ===\n";
echo "ID: " . $block['id'] . "\n";
echo "Title: " . htmlspecialchars($block['title']) . "\n";
echo "ImagePosition: $imagePosition\n";
echo "HasImage: " . ($hasImage ? 'YES' : 'NO') . "\n";
echo "HasText: " . ($hasText ? 'YES' : 'NO') . "\n\n";

echo "=== SECTION STYLE ===\n";
echo htmlspecialchars($sectionStyle) . "\n\n";

echo "=== SECTION HTML START ===\n";
echo '<section class="bg-white shadow-lg p-8 max-w-6xl mx-auto my-12" style="' . htmlspecialchars($sectionStyle) . '">' . "\n";

if ($hasImage && $imagePosition === 'left') {
    $imageStyle = '';
    if (!$hasText) {
        $imageStyle = 'width: 100%;';
    } else {
        $imageStyle = 'flex: 0 0 auto; max-width: 280px; width: 100%;';
    }
    echo '<div style="' . htmlspecialchars($imageStyle) . '">' . "\n";
    echo '  <img src="uploads/' . htmlspecialchars($block['image']) . '" ... />' . "\n";
    echo '</div>' . "\n";
}

if ($hasText) {
    $textStyle = ($imagePosition !== 'normal' && $hasImage) ? 'flex: 1 1 auto; min-width: 0;' : '';
    echo '<div style="' . htmlspecialchars($textStyle) . '">' . "\n";
    echo '  <h3>' . htmlspecialchars(substr($block['title'], 0, 50)) . '...</h3>' . "\n";
    echo '  <div>Text content...</div>' . "\n";
    echo '</div>' . "\n";
}

echo '</section>' . "\n";
echo "\n=== EXPECTED: Photo LEFT | Text RIGHT (in row) ===\n";
?>
