<?php
session_start();

echo "=== Current Session ===\n";
echo "User: " . ($_SESSION['user'] ?? 'Not logged in') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'N/A') . "\n";
echo "Admin: " . ($_SESSION['admin'] ?? 'N/A') . "\n";
echo "Permissions (raw): " . ($_SESSION['permissions'] ?? 'null') . "\n";

$perms = json_decode($_SESSION['permissions'] ?? 'null', true);
echo "Permissions (decoded): " . json_encode($perms) . "\n";
echo "Has view_audit: " . (in_array('view_audit', $perms ?? []) ? "YES ✓" : "NO ✗") . "\n";
echo "Can access admin: " . ($_SESSION['can_access_admin'] ?? 'N/A') . "\n";
?>
