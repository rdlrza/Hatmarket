<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: cart.php");
    exit();
}

$order_id = (int)$_GET['order_id'];

// Get order details
try {
    $stmt = $conn->prepare("
        SELECT o.*, u.email,
               oi.quantity, oi.price as item_price,
               p.name as product_name, p.image_url
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order_items = $stmt->fetchAll();

    if (empty($order_items)) {
        header("Location: cart.php");
        exit();
    }

    // Get the first item for order details (they'll all have the same order info)
    $order = $order_items[0];

} catch(PDOException $e) {
    error_log("Order confirmation error: " . $e->getMessage());
    header("Location: cart.php?error=confirmation");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - HatMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo getAssetUrl('css/style.css'); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                <h1 class="mb-3">Thank You for Your Order!</h1>
                <p class="lead text-muted">Your order has been successfully placed and is being processed.</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <h5 class="card-title">Order Details</h5>
                                    <p class="mb-1">Order Number: #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></p>
                                    <p class="mb-1">Date: <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                                    <p class="mb-1">Status: 
                                        <span class="badge bg-success"><?php echo ucfirst($order['status']); ?></span>
                                    </p>
                                </div>
                                <div class="col-sm-6 text-sm-end">
                                    <h5 class="card-title">Customer Details</h5>
                                    <p class="mb-1">Email: <?php echo sanitizeOutput($order['email']); ?></p>
                                </div>
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Quantity</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo getImageUrl($item['image_url']); ?>" 
                                                             alt="<?php echo sanitizeOutput($item['product_name']); ?>"
                                                             class="img-thumbnail me-3" style="width: 50px;">
                                                        <?php echo sanitizeOutput($item['product_name']); ?>
                                                    </div>
                                                </td>
                                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                <td class="text-end"><?php echo formatPrice($item['item_price']); ?></td>
                                                <td class="text-end"><?php echo formatPrice($item['item_price'] * $item['quantity']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                            <td class="text-end"><?php echo formatPrice($order['total_amount']); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                                            <td class="text-end">Free</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td class="text-end"><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="text-center">
                                <p class="mb-4">
                                    We'll send you a confirmation email with your order details and tracking information once your order ships.
                                </p>
                                <a href="<?php echo getSiteUrl(); ?>/shop.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
