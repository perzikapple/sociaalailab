<?php
require 'db.php';

echo "=== Updating Live Database - Onderzoeker Permissions ===\n\n";

$onderzokerPerms = ['access_booking', 'create_events', 'view_feedback', 'view_pages'];
$permJson = json_encode($onderzokerPerms, JSON_UNESCAPED_SLASHES);

$query = "UPDATE accounts SET permissions = :perms WHERE role = 'editor' OR role = 'onderzoeker'";
$stmt = $pdo->prepare($query);

try {
    $stmt->execute([':perms' => $permJson]);
    $count = $stmt->rowCount();
    echo "✅ Updated $count onderzoeker account(s)\n";
    echo "✅ Added view_pages permission\n";
    echo "\n✓ Onderzoekers can now see all pages in the admin panel!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
