<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

// Validate order ID
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$order_id) {
    header('Location: ' . getSiteUrl() . '/orders.php');
    exit();
}

try {
    // Get order details with shipping information
    $stmt = $conn->prepare("
        SELECT o.*, u.username, u.email,
               s.address, s.city, s.state, s.postal_code, s.country,
               s.phone
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN shipping_info s ON o.shipping_info_id = s.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: ' . getSiteUrl() . '/orders.php');
        exit();
    }

    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.image_url 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Order details error: " . $e->getMessage());
    $_SESSION['error'] = "Sorry, we couldn't retrieve your order details. Please try again later.";
    header('Location: ' . getSiteUrl() . '/orders.php');
    exit();
}

// Set page title
$page_title = "Order Details #" . str_pad($order_id, 8, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . SITE_NAME; ?></title>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo getAssetUrl('css/style.css'); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="py-5">
        <div class="container">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            Order #<?php echo str_pad($order_id, 8, '0', STR_PAD_LEFT); ?>
                        </h4>
                        <span class="badge bg-light text-dark">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="card-title">Order Information</h5>
                            <p class="mb-1">
                                <i class="fas fa-calendar me-2"></i>
                                <strong>Order Date:</strong> 
                                <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-money-bill me-2"></i>
                                <strong>Total Amount:</strong> 
                                <?php echo formatPrice($order['total_amount']); ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Status:</strong> 
                                <span class="badge bg-<?php 
                                    echo match($order['status']) {
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'shipped' => 'primary',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title">Shipping Information</h5>
                            <p class="mb-1">
                                <i class="fas fa-user me-2"></i>
                                <strong>Name:</strong> 
                                <?php echo htmlspecialchars($order['username']); ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-envelope me-2"></i>
                                <strong>Email:</strong> 
                                <?php echo htmlspecialchars($order['email']); ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-phone me-2"></i>
                                <strong>Phone:</strong> 
                                <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <strong>Address:</strong><br>
                                <?php
                                $address_parts = array_filter([
                                    $order['address'] ?? '',
                                    $order['city'] ?? '',
                                    $order['state'] ?? '',
                                    $order['postal_code'] ?? '',
                                    $order['country'] ?? ''
                                ]);
                                echo htmlspecialchars(implode(', ', $address_parts));
                                ?>
                            </p>
                        </div>
                    </div>

                    <h5 class="card-title mt-4">Order Items</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo getImageUrl($item['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                     class="img-thumbnail me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-end"><?php echo formatPrice($item['price']); ?></td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end"><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end">
                                        <strong>Total:</strong>
                                    </td>
                                    <td class="text-end">
                                        <strong><?php echo formatPrice($order['total_amount']); ?></strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if ($order['status'] === 'pending'): ?>
                        <div class="alert alert-info mt-4">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-2"></i>
                                Payment Instructions
                            </h6>
                            <p>Please complete your payment using one of the following methods:</p>
                            <ul class="mb-0">
                                <li>Bank Transfer to: <strong>1234-5678-9012</strong></li>
                                <li>E-wallet: <strong>hatmarket@gmail.com</strong></li>
                            </ul>
                            <hr>
                            <p class="mb-0">
                                After payment, please email your proof of payment to 
                                <a href="mailto:hatmarket@gmail.com">hatmarket@gmail.com</a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="<?php echo getSiteUrl(); ?>/orders.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Orders
                </a>
                <?php if ($order['status'] === 'delivered'): ?>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print Order
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
