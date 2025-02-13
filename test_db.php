<?php
require_once 'config/database.php';

try {
    // Test the connection
    $test_query = $conn->query("SELECT 1");
    echo "<h2 style='color: green;'>Database connection successful!</h2>";
    
    // Test if tables exist
    $tables = array('users', 'categories', 'products', 'orders', 'order_items');
    echo "<h3>Checking database tables:</h3>";
    echo "<ul>";
    
    foreach ($tables as $table) {
        try {
            $result = $conn->query("SELECT 1 FROM $table LIMIT 1");
            echo "<li style='color: green;'>Table '$table' exists and is accessible ✓</li>";
        } catch(PDOException $e) {
            echo "<li style='color: red;'>Table '$table' is missing or inaccessible ✗</li>";
        }
    }
    echo "</ul>";
    
    // Test if categories were imported
    $stmt = $conn->query("SELECT COUNT(*) as count FROM categories");
    $category_count = $stmt->fetch()['count'];
    echo "<p>Number of categories in database: <strong>$category_count</strong></p>";
    
} catch(PDOException $e) {
    echo "<h2 style='color: red;'>Error testing database:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
