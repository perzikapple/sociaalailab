<?php
// Capture output from verantwoord-ai.php by buffering it
ob_start();
include 'verantwoord-ai.php';
$output = ob_get_clean();

// Find debug comments
$count = 0;
if (preg_match_all('/<!-- DEBUG: Block=\{(.*?)\} \| imagePosition=\{(.*?)\} \| hasImage=\{(.*?)\} \| hasText=\{(.*?)\} -->/s', $output, $matches)) {
    echo "Found " . count($matches[0]) . " debug comments:\n\n";
    for ($i = 0; $i < count($matches[0]); $i++) {
        echo "Item " . ($i+1) . ":\n";
        echo "  Block: " . trim($matches[1][$i]) . "\n";
        echo "  imagePosition: " . trim($matches[2][$i]) . "\n";
        echo "  hasImage: " . trim($matches[3][$i]) . "\n";
        echo "  hasText: " . trim($matches[4][$i]) . "\n\n";
    }
} else {
    echo "No debug comments found. Searching for pattern...\n";
    // Try to find any DEBUG comments
    if (preg_match_all('/<!-- DEBUG:(.*?)-->/s', $output, $matches)) {
        echo "Found " . count($matches[0]) . " comments:\n";
        foreach ($matches[1] as $i => $comment) {
            echo ($i+1) . ": " . substr(trim($comment), 0, 100) . "\n";
        }
    } else {
        echo "No DEBUG comments at all found in output.\n";
    }
}
