<?php
// Fresh render, no cache
ob_start();
include 'verantwoord-ai.php';
$output = ob_get_clean();

// Look for sections with flex-direction
preg_match_all('/<section[^>]*style="([^"]*flex-direction: (row|column)[^"]*)"[^>]*>.*?<!-- DEBUG: Block=\{([^}]*)\}/s', $output, $matches);

if (count($matches[0]) > 0) {
    echo "Found " . count($matches[0]) . " sections:\n\n";
    for ($i = 0; $i < min(5, count($matches[0])); $i++) {
        echo "Item " . ($i+1) . ": " . trim($matches[3][$i]) . "\n";
        echo "Direction: " . $matches[2][$i] . "\n";
        echo "Full style: " . $matches[1][$i] . "\n\n";
    }
} else {
    echo "No sections found with flex-direction. Searching for ANY section with style...\n";
    preg_match_all('/<section[^>]*style="([^"]*)">/s', $output, $matches);
    echo "Found " . count($matches[0]) . " sections total\n";
    for ($i = 0; $i < min(3, count($matches[0])); $i++) {
        echo ($i+1) . ": " . substr($matches[1][$i], 0, 100) . "\n";
    }
}
