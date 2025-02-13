<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

try {
    $conn->beginTransaction();

    // Get cart items
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.stock 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();

    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'error' => 'Cart is empty']);
        exit();
    }

    // Calculate total amount
    $total_amount = 0;
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Get user's shipping address
    $stmt = $conn->prepare("SELECT address FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $shipping_address = $stmt->fetchColumn();

    if (!$shipping_address) {
        echo json_encode(['success' => false, 'error' => 'Please update your shipping address in your profile']);
        exit();
    }

    // Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $total_amount,
        $shipping_address,
        $_POST['payment_method'] ?? 'bank_transfer'
    ]);
    $order_id = $conn->lastInsertId();

    // Add order items and update stock
    foreach ($cart_items as $item) {
        // Check stock availability
        if ($item['stock'] < $item['quantity']) {
            $conn->rollBack();
            echo json_encode([
                'success' => false, 
                'error' => "Insufficient stock for {$item['name']}"
            ]);
            exit();
        }

        // Add order item
        $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        ]);

        // Update product stock
        $stmt = $conn->prepare("
            UPDATE products 
            SET stock = stock - ? 
            WHERE id = ?
        ");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }

    // Clear user's cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $order_id
    ]);

} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
