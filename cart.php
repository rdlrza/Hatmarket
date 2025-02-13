<?php
require_once 'includes/header.php';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please login to add items to cart';
        header('Location: ' . getSiteUrl() . '/login.php');
        exit();
    }

    $action = $_POST['action'] ?? '';
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    switch ($action) {
        case 'add':
            // Get product details
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = array();
                }

                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = array(
                        'id' => $product_id,
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'quantity' => $quantity,
                        'image' => $product['image']
                    );
                }
                $_SESSION['success'] = 'Product added to cart successfully';
            }
            break;

        case 'update':
            if (isset($_SESSION['cart'][$product_id])) {
                if ($quantity > 0) {
                    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                    $_SESSION['success'] = 'Cart updated successfully';
                } else {
                    unset($_SESSION['cart'][$product_id]);
                    $_SESSION['success'] = 'Product removed from cart';
                }
            }
            break;

        case 'remove':
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                $_SESSION['success'] = 'Product removed from cart';
            }
            break;
    }

    // Redirect back to cart page
    header('Location: ' . getSiteUrl() . '/cart.php');
    exit();
}

// Calculate cart total
$cart_total = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_total += $item['price'] * $item['quantity'];
    }
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($_SESSION['cart'])): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5>Your cart is empty</h5>
                            <p class="text-muted">Add some products to your cart and they will show up here</p>
                            <a href="<?php echo getSiteUrl(); ?>/shop.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?php echo getSiteUrl() . '/' . $item['image']; ?>" 
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                             class="img-thumbnail me-3"
                                                             style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo formatPrice($item['price']); ?></td>
                                            <td>
                                                <form action="<?php echo getSiteUrl(); ?>/cart.php" 
                                                      method="post" 
                                                      class="d-flex align-items-center">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                                    <input type="number" 
                                                           name="quantity" 
                                                           value="<?php echo $item['quantity']; ?>" 
                                                           min="0" 
                                                           class="form-control form-control-sm" 
                                                           style="width: 70px;"
                                                           onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                            <td>
                                                <form action="<?php echo getSiteUrl(); ?>/cart.php" 
                                                      method="post" 
                                                      class="d-inline">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                                    <button type="submit" 
                                                            class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Are you sure you want to remove this item?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-receipt me-2"></i>Order Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal:</span>
                        <span><?php echo formatPrice($cart_total); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Shipping:</span>
                        <span>Free</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong><?php echo formatPrice($cart_total); ?></strong>
                    </div>
                    
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <a href="<?php echo getSiteUrl(); ?>/checkout.php" class="btn btn-primary w-100">
                            <i class="fas fa-lock me-2"></i>Proceed to Checkout
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
