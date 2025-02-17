<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['item_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$item_id = $_POST['item_id'];
$action = $_POST['action'];
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'increase':
            // Check stock before increasing
            $stmt = $conn->prepare("
                SELECT ci.quantity, p.stock 
                FROM cart_items ci 
                JOIN products p ON ci.product_id = p.id 
                WHERE ci.id = ? AND ci.user_id = ?
            ");
            $stmt->execute([$item_id, $user_id]);
            $item = $stmt->fetch();
            
            if ($item && $item['quantity'] < $item['stock']) {
                $stmt = $conn->prepare("
                    UPDATE cart_items 
                    SET quantity = quantity + 1 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$item_id, $user_id]);
            }
            break;

        case 'decrease':
            $stmt = $conn->prepare("
                UPDATE cart_items 
                SET quantity = GREATEST(quantity - 1, 1)
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$item_id, $user_id]);
            break;

        case 'remove':
            $stmt = $conn->prepare("
                DELETE FROM cart_items 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$item_id, $user_id]);
            break;
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
