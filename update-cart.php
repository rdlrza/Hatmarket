<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$product_id = $input['product_id'] ?? null;
$change = $input['change'] ?? 0;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($product_id && $change) {
    $current_quantity = $_SESSION['cart'][$product_id] ?? 0;
    $new_quantity = max(0, $current_quantity + $change);
    
    if ($new_quantity > 0) {
        $_SESSION['cart'][$product_id] = $new_quantity;
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
}
?>
