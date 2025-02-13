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
        SELECT o.*, u.email 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        header("Location: cart.php");
        exit();
    }

} catch(PDOException $e) {
    error_log("Payment error: " . $e->getMessage());
    header("Location: checkout.php?error=payment");
    exit();
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
        $stmt->execute([$order_id]);

        // Commit transaction
        $conn->commit();

        // Redirect to confirmation page
        header("Location: order-confirmation.php?order_id=" . $order_id);
        exit();

    } catch(PDOException $e) {
        $conn->rollBack();
        error_log("Payment processing error: " . $e->getMessage());
        header("Location: payment.php?order_id=" . $order_id . "&error=payment");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - HatMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo getAssetUrl('css/style.css'); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="py-5">
        <div class="container">
            <h1 class="mb-4">Payment</h1>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php if ($_GET['error'] === 'payment'): ?>
                        An error occurred while processing your payment. Please try again.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Payment Details</h5>
                            <form id="paymentForm" method="post" action="payment.php?order_id=<?php echo $order_id; ?>">
                                <div class="mb-4">
                                    <h6 class="mb-3">Order Total: <?php echo formatPrice($order['total_amount']); ?></h6>
                                </div>

                                <div class="mb-4">
                                    <h6 class="mb-3">Payment Method</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                        <label class="form-check-label" for="credit_card">
                                            <i class="fab fa-cc-visa me-2"></i>Credit Card
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                        <label class="form-check-label" for="paypal">
                                            <i class="fab fa-paypal me-2"></i>PayPal
                                        </label>
                                    </div>
                                </div>

                                <div id="credit_card_form">
                                    <div class="mb-3">
                                        <label for="card_name" class="form-label">Name on Card</label>
                                        <input type="text" class="form-control" id="card_name" name="card_name" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="card_number" class="form-label">Card Number</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="card_number" name="card_number" required
                                                   pattern="[0-9]{16}" maxlength="16" placeholder="1234 5678 9012 3456">
                                            <span class="input-group-text">
                                                <i class="fab fa-cc-visa"></i>
                                                <i class="fab fa-cc-mastercard ms-2"></i>
                                                <i class="fab fa-cc-amex ms-2"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="expiry" class="form-label">Expiry Date</label>
                                            <input type="text" class="form-control" id="expiry" name="expiry" required
                                                   pattern="(0[1-9]|1[0-2])\/([0-9]{2})" placeholder="MM/YY">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cvv" class="form-label">CVV</label>
                                            <input type="text" class="form-control" id="cvv" name="cvv" required
                                                   pattern="[0-9]{3,4}" maxlength="4" placeholder="123">
                                        </div>
                                    </div>
                                </div>

                                <div id="paypal_form" style="display: none;">
                                    <div class="alert alert-info">
                                        You will be redirected to PayPal to complete your payment.
                                    </div>
                                </div>

                                <button type="submit" name="process_payment" class="btn btn-primary w-100">
                                    <i class="fas fa-lock me-2"></i>Process Payment
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment method toggle
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('credit_card_form').style.display = 
                    this.value === 'credit_card' ? 'block' : 'none';
                document.getElementById('paypal_form').style.display = 
                    this.value === 'paypal' ? 'block' : 'none';
            });
        });

        // Form validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const form = this;
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });

        // Format card number
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            this.value = value;
        });

        // Format expiry date
        document.getElementById('expiry').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '/' + value.slice(2);
            }
            this.value = value;
        });

        // Format CVV
        document.getElementById('cvv').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>
