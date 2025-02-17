<?php
require_once 'config/database.php';

try {
    // Delete existing admin user if exists
    $stmt = $conn->prepare("DELETE FROM users WHERE email = 'admin@vintagecaps.co'");
    $stmt->execute();

    // Create new admin user with correct password hash
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@vintagecaps.co', $hashed_password, true]);
    
    echo "Admin user created successfully!<br>";
    echo "Email: admin@vintagecaps.co<br>";
    echo "Password: admin123<br>";
    echo "<a href='login.php'>Go to Login Page</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
