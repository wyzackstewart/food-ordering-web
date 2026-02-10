<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = getUserId();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;
    
    $conn = getDBConnection();
    
    switch ($action) {
        case 'add':
            // Check if item already in cart
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $userId, $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update quantity
                $row = $result->fetch_assoc();
                $newQuantity = $row['quantity'] + $quantity;
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->bind_param("ii", $newQuantity, $row['id']);
                $stmt->execute();
            } else {
                // Add new item
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $userId, $productId, $quantity);
                $stmt->execute();
            }
            break;
            
        case 'update':
            if ($quantity > 0) {
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("iii", $quantity, $userId, $productId);
                $stmt->execute();
            } else {
                // Remove item if quantity is 0
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $userId, $productId);
                $stmt->execute();
            }
            break;
            
        case 'remove':
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $userId, $productId);
            $stmt->execute();
            break;
            
        case 'clear':
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            break;
    }
    
    // Return updated cart count
    $cartCount = getCartCount($userId);
    echo json_encode(['success' => true, 'cart_count' => $cartCount]);
    exit;
}

// Get cart items
$conn = getDBConnection();
$cartItems = [];
$subtotal = 0;

$query = "SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, 
                 p.description, p.price, p.image_url 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = ? AND p.is_available = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['total'] = $row['price'] * $row['quantity'];
    $subtotal += $row['total'];
    $cartItems[] = $row;
}

$deliveryFee = 2.99;
$tax = $subtotal * 0.08;
$total = $subtotal + $deliveryFee + $tax;

$pageTitle = "Shopping Cart - FoodExpress";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <div class="container">
            <h1 class="page-title">Your Shopping Cart</h1>
            
            <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Add some delicious items from our menu!</p>
                <a href="menu.php" class="btn btn-primary">Browse Menu</a>
            </div>
            <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <div class="cart-header">
                        <h2>Cart Items (<?php echo count($cartItems); ?>)</h2>
                        <button class="btn btn-danger btn-sm" id="clearCartBtn">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </div>
                    
                    <div class="cart-items-list">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <div class="item-image">
                                <img src="<?php echo $item['image_url'] ?: 'assets/images/placeholder/food.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                <div class="item-price"><?php echo formatPrice($item['price']); ?></div>
                            </div>
                            <div class="item-controls">
                                <div class="quantity-control">
                                    <button class="quantity-btn minus">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" min="1" max="10">
                                    <button class="quantity-btn plus">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <button class="btn btn-danger btn-sm remove-item">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                            <div class="item-total">
                                <?php echo formatPrice($item['total']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-card">
                        <h2>Order Summary</h2>
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span id="subtotal"><?php echo formatPrice($subtotal); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Delivery Fee</span>
                                <span><?php echo formatPrice($deliveryFee); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tax (8%)</span>
                                <span id="tax"><?php echo formatPrice($tax); ?></span>
                            </div>
                            <div class="summary-divider"></div>
                            <div class="summary-row total">
                                <strong>Total</strong>
                                <strong id="total"><?php echo formatPrice($total); ?></strong>
                            </div>
                        </div>
                        
                        <div class="summary-actions">
                            <a href="checkout.php" class="btn btn-primary btn-block">
                                <i class="fas fa-shopping-bag"></i> Proceed to Checkout
                            </a>
                            <a href="menu.php" class="btn btn-outline btn-block">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
    // Cart functionality
    document.querySelectorAll('.quantity-btn').forEach(button => {
        button.addEventListener('click', function() {
            const item = this.closest('.cart-item');
            const input = item.querySelector('.quantity-input');
            let quantity = parseInt(input.value);
            
            if (this.classList.contains('minus')) {
                quantity = Math.max(1, quantity - 1);
            } else {
                quantity = Math.min(10, quantity + 1);
            }
            
            input.value = quantity;
            updateCartItem(item, quantity);
        });
    });
    
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            const item = this.closest('.cart-item');
            removeCartItem(item);
        });
    });
    
    document.getElementById('clearCartBtn')?.addEventListener('click', function() {
        if (confirm('Are you sure you want to clear your cart?')) {
            clearCart();
        }
    });
    
    function updateCartItem(item, quantity) {
        const productId = item.getAttribute('data-product-id');
        
        fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update&product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartUI(item, quantity, data.cart_count);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to update cart', 'error');
        });
    }
    
    function removeCartItem(item) {
        const productId = item.getAttribute('data-product-id');
        
        fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=remove&product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                item.remove();
                updateCartCount(data.cart_count);
                updateCartSummary();
                showNotification('Item removed from cart', 'success');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to remove item', 'error');
        });
    }
    
    function clearCart() {
        fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clear'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector('.cart-items-list').innerHTML = 
                    '<div class="empty-cart">' +
                    '<i class="fas fa-shopping-cart"></i>' +
                    '<h2>Your cart is empty</h2>' +
                    '<a href="menu.php" class="btn btn-primary">Browse Menu</a>' +
                    '</div>';
                updateCartCount(data.cart_count);
                showNotification('Cart cleared', 'success');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to clear cart', 'error');
        });
    }
    
    function updateCartUI(item, quantity, cartCount) {
        const price = parseFloat(item.querySelector('.item-price').textContent.replace('₹', ''));
        const total = price * quantity;
        item.querySelector('.item-total').textContent = '₹' + total.toFixed(2);
        updateCartCount(cartCount);
        updateCartSummary();
    }
    
    function updateCartSummary() {
        let subtotal = 0;
        
        document.querySelectorAll('.cart-item').forEach(item => {
            const totalText = item.querySelector('.item-total').textContent;
            const total = parseFloat(totalText.replace('₹', ''));
            subtotal += total;
        });
        
        const tax = subtotal * 0.08;
        const deliveryFee = 2.99;
        const total = subtotal + tax + deliveryFee;
        
        document.getElementById('subtotal').textContent = '₹' + subtotal.toFixed(2);
        document.getElementById('tax').textContent = '₹' + tax.toFixed(2);
        document.getElementById('total').textContent = '₹' + total.toFixed(2);
    }
    
    function updateCartCount(count) {
        document.querySelectorAll('.cart-count').forEach(element => {
            element.textContent = count;
        });
    }
    
    function showNotification(message, type) {
        // Notification implementation
        alert(message); // Simple alert for now
    }
    </script>
</body>
</html>