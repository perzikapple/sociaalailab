<?php
require 'db.php';

// Get all accounts and their permissions
$query = "SELECT email, role, admin, permissions FROM accounts ORDER BY email";
$result = $pdo->query($query);

echo "=== All Accounts in Database ===\n\n";

foreach ($result as $row) {
    $email = $row['email'];
    $role = $row['role'];
    $admin = $row['admin'];
    $perms = json_decode($row['permissions'], true);
    
    echo "Email: $email\n";
    echo "Role: $role\n";
    echo "Admin: $admin\n";
    echo "Permissions: " . json_encode($perms) . "\n";
    echo "Has access_booking: " . (in_array('access_booking', $perms ?? []) ? "YES ✓" : "NO ✗") . "\n";
    echo "---\n";
}
?>
