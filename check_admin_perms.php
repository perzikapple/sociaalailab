<?php
require 'db.php';

$query = "SELECT email, role, admin, permissions FROM accounts WHERE role IN ('administrator', 'superadmin') ORDER BY email";
$result = $pdo->query($query);

echo "=== Administrator Accounts ===\n\n";

foreach ($result as $row) {
    $email = $row['email'];
    $role = $row['role'];
    $admin = $row['admin'];
    $perms = json_decode($row['permissions'], true);
    
    echo "Email: $email\n";
    echo "Role: $role\n";
    echo "Admin: $admin\n";
    echo "Permissions (raw): " . $row['permissions'] . "\n";
    echo "Permissions (decoded): " . json_encode($perms) . "\n";
    echo "Has view_audit: " . (in_array('view_audit', $perms ?? []) ? "YES ✓" : "NO ✗") . "\n";
    echo "---\n";
}
?>
