<?php
// Fix live database - add view_audit to administrator accounts
require 'db.php';

echo "=== Updating Live Database ===\n\n";

$adminPerms = ['create_users', 'edit_users', 'delete_users', 'manage_banners', 'manage_events', 'manage_pages', 'delete_events', 'delete_pages', 'optimize_images', 'approve_content', 'access_booking', 'view_audit'];
$permJson = json_encode($adminPerms, JSON_UNESCAPED_SLASHES);

$query = "UPDATE accounts SET permissions = :perms WHERE role = 'administrator'";
$stmt = $pdo->prepare($query);

try {
    $stmt->execute([':perms' => $permJson]);
    $count = $stmt->rowCount();
    echo "✅ Updated $count administrator account(s)\n";
    echo "✅ Added view_audit permission\n";
    echo "\nNow logout and login again to refresh your session!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
