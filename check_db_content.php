<?php
$db = new mysqli('localhost', 'root', '', 'sociaalai');

echo "=== TABLE STRUCTURES ===\n";
echo "\nPAGES columns:\n";
$result = $db->query("DESCRIBE pages");
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}

echo "\nEVENTS columns:\n";
$result = $db->query("DESCRIBE events");
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}

echo "\n\n=== CHECKING FOR BR TAGS IN ALL TEXT FIELDS ===\n";

// Check pages table
echo "\n--- PAGES (body column) ---\n";
$result = $db->query("SELECT id, title, body FROM pages LIMIT 2");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "\nPage {$row['id']}: {$row['title']}\n";
        if (strpos($row['body'], '&lt;br') !== false) {
            echo "✓ FOUND &lt;br&gt; tags\n";
            echo "Preview: " . substr($row['body'], 0, 200) . "\n";
        }
        if (strpos($row['body'], '<br') !== false) {
            echo "✓ FOUND <br> tags\n";
            echo "Preview: " . substr($row['body'], 0, 200) . "\n";
        }
    }
}

echo "\n--- EVENTS (all text fields) ---\n";
$result = $db->query("SELECT id, title, description, event_summary, meer_info FROM events WHERE show_on_homepage = 1 ORDER BY sort_order LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "\nEvent {$row['id']}: {$row['title']}\n";
    $fieldsToCheck = ['description', 'event_summary', 'meer_info'];
    foreach ($fieldsToCheck as $field) {
        if (!empty($row[$field])) {
            if (strpos($row[$field], '&lt;br') !== false) {
                echo "  ✓ FOUND &lt;br&gt; tags in '$field'\n";
                echo "  Preview: " . substr($row[$field], 0, 150) . "\n";
            }
            if (strpos($row[$field], '<br') !== false) {
                echo "  ✓ FOUND <br> tags in '$field'\n";
                echo "  Preview: " . substr($row[$field], 0, 150) . "\n";
            }
        }
    }
}
?>
