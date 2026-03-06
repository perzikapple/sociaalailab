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
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .success { color: green; background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .error { color: red; background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .btn { padding: 12px 24px; background: #00811F; color: white; text-decoration: none; border-radius: 4px; display: inline-block; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>Admin Account Aanmaken</h1>";

echo "<div class='warning'>";
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
        $stmt = $pdo->prepare("UPDATE accounts SET wachtwoord = ? WHERE email = ?");
        $stmt->execute([$adminPassword, $adminEmail]);
        
        echo "<div class='success'>";
        echo "<h2>✓ Account Bijgewerkt!</h2>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($adminEmail) . "</p>";
        echo "<p><strong>Wachtwoord:</strong> " . htmlspecialchars($adminPassword) . "</p>";
        echo "<p><em>(Wachtwoord is zichtbaar opgeslagen in database)</em></p>";
        echo "</div>";
    } else {
        // Maak nieuw account aan
        $stmt = $pdo->prepare("INSERT INTO accounts (email, wachtwoord) VALUES (?, ?)");
        $stmt->execute([$adminEmail, $adminPassword]);
        
        echo "<div class='success'>";
        echo "<h2>✓ Account Aangemaakt!</h2>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($adminEmail) . "</p>";
        echo "<p><strong>Wachtwoord:</strong> " . htmlspecialchars($adminPassword) . "</p>";
        echo "<p><em>(Wachtwoord is zichtbaar opgeslagen in database)</em></p>";
        echo "</div>";
    }
    
    echo "<p><a href='login.php' class='btn'>Ga naar Login</a></p>";
    
    echo "<hr style='margin: 30px 0;'>";
    echo "<p style='color: #666;'><small>Verwijder dit bestand na gebruik.</small></p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>✗ Fout</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>
