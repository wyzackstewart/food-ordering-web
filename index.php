<?php
require_once 'config.php';

// Get featured products
$conn = getDBConnection();
$featuredProducts = [];

$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          JOIN categories c ON p.category_id = c.id 
          WHERE p.is_available = 1 
          ORDER BY RAND() 
          LIMIT 6";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $featuredProducts[] = $row;
    }
}

// Get categories
$categories = [];
$catResult = $conn->query("SELECT * FROM categories LIMIT 6");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get cart count
$cartCount = 0;
if (isLoggedIn()) {
    $cartCount = getCartCount(getUserId());
}

// Set page title
$pageTitle = "FoodExpress - Order Delicious Food Online";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Delicious Food Delivered to Your Doorstep</h1>
                    <p>Order from 100+ restaurants in your area. Fast delivery, fresh meals, and great prices!</p>
                    
                    <?php if (!isLoggedIn()): ?>
                    <div class="hero-buttons">
                        <a href="auth/register.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Get Started
                        </a>
                        <a href="menu.php" class="btn btn-outline">
                            <i class="fas fa-utensils"></i> Browse Menu
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="hero-buttons">
                        <a href="menu.php" class="btn btn-primary">
                            <i class="fas fa-utensils"></i> Order Now
                        </a>
                        <a href="user/dashboard.php" class="btn btn-outline">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="hero-image">
                    <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Delicious Food">
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories">
        <div class="container">
            <h2 class="section-title">Popular Categories</h2>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                <a href="menu.php?category=<?php echo $category['id']; ?>" class="category-card">
                    <div class="category-image">
                        <img src="<?php echo $category['image_url'] ?: 'assets/images/placeholder/category.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($category['name']); ?>">
                    </div>
                    <div class="category-info">
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <h2 class="section-title">Today's Specials</h2>
            <div class="products-grid">
                <?php foreach ($featuredProducts as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo $product['image_url'] ?: 'assets/images/placeholder/food.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <p class="description"><?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?></p>
                        <div class="product-footer">
                            <span class="price"><?php echo formatPrice($product['price']); ?></span>
                            <?php if (isLoggedIn()): ?>
                            <button class="btn btn-primary btn-sm add-to-cart" 
                                    data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            <?php else: ?>
                            <a href="auth/login.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-sign-in-alt"></i> Login to Order
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-search"></i>
                        <span class="step-number">1</span>
                    </div>
                    <h3>Browse Menu</h3>
                    <p>Choose from our delicious selection</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="step-number">2</span>
                    </div>
                    <h3>Add to Cart</h3>
                    <p>Select items and customize your order</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-map-marker-alt"></i>
                        <span class="step-number">3</span>
                    </div>
                    <h3>Enter Address</h3>
                    <p>Provide your delivery location</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-truck"></i>
                        <span class="step-number">4</span>
                    </div>
                    <h3>Fast Delivery</h3>
                    <p>Enjoy fresh food at your doorstep</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    <script>
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            addToCart(productId);
        });
    });

    function addToCart(productId) {
        fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add',
                product_id: productId,
                quantity: 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Item added to cart!', 'success');
                updateCartCount(data.cart_count);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to add item to cart', 'error');
        });
    }

    function updateCartCount(count) {
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = count;
        });
    }

    function showNotification(message, type) {
        // Create notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        // Add to body
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
    }
    </script>
</body>
</html>