<?php
require_once 'includes/common.php';

// Set page title
$page_title = 'Welcome to ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo getAssetUrl('css/style.css'); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <?php
    require_once 'includes/header.php';

    // Get featured products
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC 
        LIMIT 8
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get categories
    $categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <main>
        <div class="container my-5">
            <!-- Hero Section -->
            <div class="row align-items-center mb-5">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold">Welcome to <?php echo SITE_NAME; ?></h1>
                    <p class="lead">Discover the latest collection of premium caps from top brands.</p>
                    <a href="<?php echo getSiteUrl(); ?>/shop.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Shop Now
                    </a>
                </div>
                <div class="col-md-6">
                    <img src="<?php echo getAssetUrl('images/hero-image.jpg'); ?>" 
                         alt="Featured Caps" 
                         class="img-fluid rounded shadow">
                </div>
            </div>

            <!-- Featured Products -->
            <div class="row mb-4">
                <div class="col">
                    <h2 class="text-center mb-4">Featured Products</h2>
                </div>
            </div>
            
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-md-3">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($product['image'])): ?>
                        <img src="<?php echo getSiteUrl() . '/' . $product['image']; ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                        <img src="<?php echo getAssetUrl('images/product-placeholder.jpg'); ?>" 
                             class="card-img-top" 
                             alt="Product placeholder"
                             style="height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </p>
                            <p class="card-text">
                                <?php echo substr(htmlspecialchars($product['description']), 0, 100) . '...'; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0"><?php echo formatPrice($product['price']); ?></span>
                                <form action="<?php echo getSiteUrl(); ?>/cart.php" method="post" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Shop by Brand -->
            <div class="row mt-5">
                <div class="col">
                    <h2 class="text-center mb-4">Shop by Brand</h2>
                </div>
            </div>
            
            <div class="row g-4 justify-content-center">
                <?php foreach ($categories as $category): ?>
                <div class="col-md-3">
                    <a href="<?php echo getSiteUrl(); ?>/shop.php?category=<?php echo $category['id']; ?>" 
                       class="text-decoration-none">
                        <div class="card h-100 shadow-sm hover-lift">
                            <?php if (!empty($category['image'])): ?>
                            <img src="<?php echo getSiteUrl() . '/' . $category['image']; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($category['name']); ?>"
                                 style="height: 150px; object-fit: cover;">
                            <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                 style="height: 150px;">
                                <i class="fas fa-hat-cowboy fa-3x text-muted"></i>
                            </div>
                            <?php endif; ?>
                            <div class="card-body text-center">
                                <h5 class="card-title text-dark mb-0">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </h5>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo getAssetUrl('js/main.js'); ?>"></script>
</body>
</html>
