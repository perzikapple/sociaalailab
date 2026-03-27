<?php
require 'db.php';

// Admin gegevens - pas deze aan
$adminEmail = 'admin@sociaalai.nl';
$adminPassword = 'admin123';  // PLAIN TEXT - niet veilig!

echo "<!DOCTYPE html>
<html lang='nl'>
<head>
    <meta charset='UTF-8'>
    <title>Admin Account Aanmaken</title>
    <link rel='icon' type='image/png' href='images/Pixels_icon.png'>
    <link rel='stylesheet' href='style.css?v=" . filemtime(__DIR__.'/style.css') . "'>
</head>
<body class='create-admin-body'>
    <div class='create-admin-container'>";

echo "<h1>Admin Account Aanmaken</h1>";

echo "<div class='create-admin-warning'>";
echo "<strong>⚠️ VEILIGHEIDSRISICO:</strong><br>";
echo "Dit systeem slaat wachtwoorden op als plain text (zonder beveiliging).<br>";
echo "Dit is NIET veilig voor productie!";
echo "</div>";

try {
    // Check of account al bestaat
    $check = $pdo->prepare("SELECT id FROM accounts WHERE email = ?");
    $check->execute([$adminEmail]);
    
    if ($check->fetch()) {
        // Update bestaand account
        $stmt = $pdo->prepare("UPDATE accounts SET wachtwoord = ?, admin = 1, role = 'superadmin' WHERE email = ?");
        $stmt->execute([$adminPassword, $adminEmail]);
        
        echo "<div class='create-admin-success'>";
        echo "<h2>✓ Account Bijgewerkt!</h2>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($adminEmail) . "</p>";
        echo "<p><strong>Wachtwoord:</strong> " . htmlspecialchars($adminPassword) . "</p>";
        echo "<p><em>(Wachtwoord is zichtbaar opgeslagen in database)</em></p>";
        echo "</div>";
    } else {
        // Maak nieuw account aan
        $stmt = $pdo->prepare("INSERT INTO accounts (email, wachtwoord, admin, role) VALUES (?, ?, 1, 'superadmin')");
        $stmt->execute([$adminEmail, $adminPassword]);
        
        echo "<div class='create-admin-success'>";
        echo "<h2>✓ Account Aangemaakt!</h2>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($adminEmail) . "</p>";
        echo "<p><strong>Wachtwoord:</strong> " . htmlspecialchars($adminPassword) . "</p>";
        echo "<p><em>(Wachtwoord is zichtbaar opgeslagen in database)</em></p>";
        echo "</div>";
    }
    
    echo "<p><a href='login.php' class='create-admin-btn'>Ga naar Login</a></p>";
    
    echo "<hr class='create-admin-separator'>";
    echo "<p class='create-admin-muted'><small>Verwijder dit bestand na gebruik.</small></p>";
    
} catch (PDOException $e) {
    echo "<div class='create-admin-error'>";
    echo "<h2>✗ Fout</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>
