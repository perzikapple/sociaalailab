<?php
// Database connection
$servername = "localhost";
$username = "root"; // Change as needed
$password = ""; // Change as needed
$dbname = "sociaalai"; // Change to your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Validate input
    if (empty($user) || empty($pass)) {
        echo "Username and password are required.";
        exit;
    }

    // Hash the password for security
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    // Insert user into database
    $sql = "INSERT INTO accounts (email, wachtwoord) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user, $hashed_password);

    if ($stmt->execute()) {
        echo "Registration successful!";
        // Redirect to login page or success page
        header("Location: login.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>