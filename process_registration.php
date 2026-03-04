<?php

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "sociaalai"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    if (empty($user) || empty($pass)) {
        echo "Username and password are required.";
        exit;
    }

    $sql = "INSERT INTO accounts (email, wachtwoord) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user, $pass);

    if ($stmt->execute()) {
        echo "Registration successful!";
        $newId = $conn->insert_id;
        $safeUser = $conn->real_escape_string($user);
        $conn->query("INSERT INTO audit_logs (action, table_name, record_id, details, performed_by, created_at) VALUES ('create', 'accounts', '" . $newId . "', 'new account registered', '" . $safeUser . "', NOW())");
        header("Location: login.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>