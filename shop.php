<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

try {
    // Build the query
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE 1=1";
    $params = array();

    if ($category_id > 0) {
        $query .= " AND p.category_id = ?";
        $params[] = $category_id;
    }

    if (!empty($search)) {
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
    }

    // Add sorting
    switch ($sort) {
        case 'price_low':
            $query .= " ORDER BY p.price ASC";
            break;
        case 'price_high':
            $query .= " ORDER BY p.price DESC";
            break;
        case 'name':
            $query .= " ORDER BY p.name ASC";
            break;
        default: // newest
            $query .= " ORDER BY p.created_at DESC";
    }

    // Get total count for pagination
    $count_stmt = $conn->prepare(str_replace('p.*, c.name as category_name', 'COUNT(*) as total', $query));
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_items / $per_page);

    // Add pagination
    $offset = ($page - 1) * $per_page;
    $query .= " LIMIT $per_page OFFSET $offset";

    // Get products
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all categories for filter
    $categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Shop error: " . $e->getMessage());
    $error = "An error occurred while loading the products. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - HatMarket</title>
    <meta name="description" content="Shop for high-quality branded caps including Nike, Adidas, Puma, and NBA caps.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo getAssetUrl('css/style.css'); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container my-5">
            <!-- Filters and Search -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <form action="" method="get" class="d-flex gap-2">
                        <?php if ($category_id): ?>
                            <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        <input type="text" 
                               name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               class="form-control" 
                               placeholder="Search products...">
                        <select name="sort" class="form-select" style="width: auto;" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name: A to Z</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                        <?php if (!empty($search) || $category_id || $sort !== 'newest'): ?>
                            <a href="<?php echo getSiteUrl(); ?>/shop.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="row">
                <!-- Categories Sidebar -->
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tags me-2"></i>Categories
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <a href="<?php echo getSiteUrl(); ?>/shop.php" 
                                   class="list-group-item list-group-item-action <?php echo !$category_id ? 'active' : ''; ?>">
                                    All Products
                                </a>
                                <?php foreach ($categories as $category): ?>
                                    <a href="<?php echo getSiteUrl(); ?>/shop.php?category=<?php echo $category['id']; ?>" 
                                       class="list-group-item list-group-item-action <?php echo $category_id === (int)$category['id'] ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="col-md-9">
                    <?php if (empty($products)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h3>No products found</h3>
                            <p class="text-muted">Try adjusting your search or filter to find what you're looking for.</p>
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-3 g-4">
                            <?php foreach ($products as $product): ?>
                                <div class="col">
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

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $sort !== 'newest' ? '&sort=' . $sort : ''; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $sort !== 'newest' ? '&sort=' . $sort : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $sort !== 'newest' ? '&sort=' . $sort : ''; ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo getAssetUrl('js/main.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('js/cart.js'); ?>"></script>
</body>
</html>
