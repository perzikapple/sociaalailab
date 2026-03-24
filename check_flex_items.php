<?php
ob_start();
include 'verantwoord-ai.php';
$output = ob_get_clean();

// Find the "Ondersteunt mensen" section
if (preg_match('/<!-- DEBUG: Block=\{Ondersteunt[^}]*\}.*?<\/section>/s', $output, $match)) {
    $section = $match[0];
    
    // Extract the style property from divs
    echo "Section containing 'Ondersteunt mensen' (Foto links):\n";
    echo "==============================================\n\n";
    
    // Find all divs with styles
    preg_match_all('/<div style="([^"]*)">/s', $section, $divMatches);
    
    echo "Found " . count($divMatches[0]) . " divs with styles:\n\n";
    for ($i = 0; $i < count($divMatches[0]); $i++) {
        echo "Div " . ($i+1) . " style:\n";
        echo "  " . $divMatches[1][$i] . "\n\n";
    }
    
    // Check for img tags
    if (preg_match('/<img[^>]*src="([^"]*)"[^>]*>/', $section, $imgMatch)) {
        echo "Image src: " . $imgMatch[1] . "\n";
    }
} else {
    echo "Could not find section\n";
}
