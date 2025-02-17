<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get cart items
$stmt = $conn->prepare("
    SELECT ci.*, p.name, p.price, p.image, p.stock
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Get payment methods
$stmt = $conn->prepare("SELECT * FROM payment_methods WHERE is_active = 1");
$stmt->execute();
$payment_methods = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Vintage Caps Co</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <h2 class="mb-4">Shopping Cart</h2>
        
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">Your cart is empty. <a href="index.php">Continue shopping</a></div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item border-bottom pb-3 mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-2">
                                            <img src="assets/images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                                 class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>
                                        <div class="col-4">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <p class="text-muted mb-0">$<?php echo number_format($item['price'], 2); ?></p>
                                        </div>
                                        <div class="col-3">
                                            <div class="input-group">
                                                <button class="btn btn-outline-secondary update-quantity" 
                                                        data-item-id="<?php echo $item['id']; ?>" 
                                                        data-action="decrease">-</button>
                                                <input type="number" class="form-control text-center" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" max="<?php echo $item['stock']; ?>" readonly>
                                                <button class="btn btn-outline-secondary update-quantity" 
                                                        data-item-id="<?php echo $item['id']; ?>" 
                                                        data-action="increase">+</button>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <p class="mb-0">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                        </div>
                                        <div class="col-1">
                                            <button class="btn btn-link text-danger remove-item" 
                                                    data-item-id="<?php echo $item['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold">Total</span>
                                <span class="fw-bold">$<?php echo number_format($total, 2); ?></span>
                            </div>
                            
                            <form id="checkout-form" action="process_payment.php" method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Select Payment Method</label>
                                    <?php foreach ($payment_methods as $method): ?>
                                        <div class="form-check payment-method-option mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" 
                                                   id="<?php echo $method['code']; ?>" 
                                                   value="<?php echo $method['id']; ?>" required>
                                            <label class="form-check-label d-flex align-items-center" 
                                                   for="<?php echo $method['code']; ?>">
                                                <img src="assets/images/payment/<?php echo $method['icon']; ?>" 
                                                     alt="<?php echo $method['name']; ?>" 
                                                     class="payment-icon me-2" style="width: 40px;">
                                                <?php echo $method['name']; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    Proceed to Payment
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update quantity
            document.querySelectorAll('.update-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.dataset.itemId;
                    const action = this.dataset.action;
                    
                    fetch('update_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `item_id=${itemId}&action=${action}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
                });
            });

            // Remove item
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to remove this item?')) {
                        const itemId = this.dataset.itemId;
                        
                        fetch('update_cart.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `item_id=${itemId}&action=remove`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
