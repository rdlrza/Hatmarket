<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vintage Caps Co - Premium Cap Collection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 2rem;
        }
        .product-card {
            transition: transform 0.3s;
            margin-bottom: 1rem;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-card img {
            height: 200px;
            object-fit: cover;
        }
        .category-section {
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .category-section:nth-child(even) {
            background: #f8f9fa;
        }
        .category-title {
            position: relative;
            margin-bottom: 2rem;
            text-align: center;
        }
        .category-title::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: #2c3e50;
            margin: 10px auto;
        }
        .brand-icon {
            font-size: 2rem;
            margin-right: 0.5rem;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php
    session_start();
    require_once 'config/database.php';

    // Get categories and their products
    $stmt = $conn->query("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.name
    ");
    $categories = $stmt->fetchAll();
    ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1>Welcome to Vintage Caps Co</h1>
            <p>Your Premium Destination for Authentic Sports Caps</p>
            <a href="#categories" class="btn btn-primary btn-lg">Shop by Brand</a>
        </div>
    </div>

    <!-- Categories Section -->
    <div id="categories">
        <?php foreach ($categories as $category): 
            // Skip categories with no products
            if ($category['product_count'] == 0) continue;
            
            // Get products for this category
            $stmt = $conn->prepare("
                SELECT * FROM products 
                WHERE category_id = ? 
                ORDER BY name
            ");
            $stmt->execute([$category['id']]);
            $products = $stmt->fetchAll();
        ?>
            <section class="category-section" id="category-<?php echo $category['id']; ?>">
                <div class="container">
                    <h2 class="category-title">
                        <?php
                        // Add brand-specific icons
                        $icon = '';
                        $brand = strtolower($category['name']);
                        if (strpos($brand, 'nike') !== false) {
                            $icon = '<i class="fab fa-nike brand-icon"></i>';
                        } elseif (strpos($brand, 'adidas') !== false) {
                            $icon = '<i class="fab fa-adversal brand-icon"></i>';
                        } elseif (strpos($brand, 'nba') !== false) {
                            $icon = '<i class="fas fa-basketball-ball brand-icon"></i>';
                        } elseif (strpos($brand, 'puma') !== false) {
                            $icon = '<i class="fas fa-cat brand-icon"></i>';
                        }
                        echo $icon . htmlspecialchars($category['name']) . ' Collection';
                        ?>
                    </h2>
                    
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card product-card">
                                    <img src="assets/images/products/<?php echo $product['image'] ?: 'placeholder.jpg'; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text">₱<?php echo number_format($product['price'], 2); ?></p>
                                        <a href="product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($products)): ?>
                        <div class="text-center py-5">
                            <p class="text-muted">No products available in this category yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
