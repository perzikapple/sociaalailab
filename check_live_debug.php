<?php
// Debug script to check live vs local state
require 'db.php';

echo "=== LIVE SERVER DEBUG ===\n\n";

// Check 1: Code version
echo "1. Checking admin.php for view_audit permission:\n";
$adminCode = file_get_contents('admin.php');
if (strpos($adminCode, "'view_audit'") !== false) {
    echo "   ✅ Code has view_audit permission\n";
} else {
    echo "   ❌ Code MISSING view_audit permission - needs git pull\n";
}

// Check 2: Database state
echo "\n2. Checking database permissions:\n";
$query = "SELECT email, role, permissions FROM accounts WHERE role = 'administrator' LIMIT 1";
$result = $pdo->query($query)->fetch();

if ($result) {
    $perms = json_decode($result['permissions'], true);
    echo "   Email: " . $result['email'] . "\n";
    echo "   Role: " . $result['role'] . "\n";
    echo "   Permissions: " . json_encode($perms) . "\n";
    
    if (in_array('view_audit', $perms ?? [])) {
        echo "   ✅ Database has view_audit permission\n";
    } else {
        echo "   ❌ Database MISSING view_audit permission\n";
    }
} else {
    echo "   ❌ No administrator account found\n";
}

// Check 3: Session info
echo "\n3. Current session:\n";
session_start();
echo "   User: " . ($_SESSION['user'] ?? 'Not logged in') . "\n";
echo "   Role: " . ($_SESSION['role'] ?? 'N/A') . "\n";
$sessPerms = json_decode($_SESSION['permissions'] ?? 'null', true);
echo "   Permissions: " . json_encode($sessPerms) . "\n";
echo "   Has view_audit in session: " . (in_array('view_audit', $sessPerms ?? []) ? "YES ✓" : "NO ✗") . "\n";

echo "\n=== WHAT TO DO ===\n";
echo "If code is ❌: Run 'git pull' on live server to get latest code\n";
echo "If database is ❌: Run the account permission update on live\n";
echo "If session is ❌: Logout and login again after fixes\n";
?>
