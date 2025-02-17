<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['payment_method'])) {
    header("Location: cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$payment_method_id = $_POST['payment_method'];

try {
    $conn->beginTransaction();

    // Get cart items
    $stmt = $conn->prepare("
        SELECT ci.*, p.price, p.stock 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();

    if (empty($cart_items)) {
        header("Location: cart.php");
        exit();
    }

    // Calculate total
    $total_amount = 0;
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, payment_method_id, status, payment_status)
        VALUES (?, ?, ?, 'pending', 'pending')
    ");
    $stmt->execute([$user_id, $total_amount, $payment_method_id]);
    $order_id = $conn->lastInsertId();

    // Add order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($cart_items as $item) {
        $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);

        // Update product stock
        $update_stock = $conn->prepare("
            UPDATE products 
            SET stock = stock - ? 
            WHERE id = ?
        ");
        $update_stock->execute([$item['quantity'], $item['product_id']]);
    }

    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $conn->commit();

    // Get payment method details
    $stmt = $conn->prepare("SELECT * FROM payment_methods WHERE id = ?");
    $stmt->execute([$payment_method_id]);
    $payment_method = $stmt->fetch();

    // Redirect based on payment method
    switch ($payment_method['code']) {
        case 'gcash':
            header("Location: payment/gcash.php?order_id=" . $order_id);
            break;
        case 'maya':
            header("Location: payment/maya.php?order_id=" . $order_id);
            break;
        case 'grabpay':
            header("Location: payment/grabpay.php?order_id=" . $order_id);
            break;
        case 'debit':
            header("Location: payment/debit.php?order_id=" . $order_id);
            break;
        default:
            header("Location: order_confirmation.php?order_id=" . $order_id);
    }

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = "An error occurred while processing your order. Please try again.";
    header("Location: cart.php");
}
?>
