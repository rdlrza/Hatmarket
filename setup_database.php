<?php
require_once 'config/database.php';

try {
    // Create admin user if not exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = 'admin@vintagecaps.co'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if (!$admin) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@vintagecaps.co', $password, true]);
        echo "Admin user created successfully!\n";
    } else {
        echo "Admin user already exists!\n";
    }

    echo "Database setup completed successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
