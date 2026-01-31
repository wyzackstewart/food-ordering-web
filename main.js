// Global variables
let cart = [];
let currentUser = null;
let foods = [];

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize based on current page
    const currentPage = window.location.pathname.split('/').pop();
    
    if (currentPage === 'index.html' || currentPage === '' || currentPage === 'index') {
        initHomePage();
    } else if (currentPage === 'login.html') {
        initLoginPage();
    } else if (currentPage === 'register.html') {
        initRegisterPage();
    }
});

// Initialize Home Page
function initHomePage() {
    // Load authentication state
    loadAuthState();
    
    // Load food menu
    loadFoodMenu();
    
    // Setup event listeners
    document.getElementById('searchInput')?.addEventListener('input', filterFoods);
    document.getElementById('categoryFilter')?.addEventListener('change', filterFoods);
    document.getElementById('placeOrderBtn')?.addEventListener('click', placeOrder);
}

// Initialize Login Page
function initLoginPage() {
    document.getElementById('loginForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        validateLoginForm();
    });
}

// Initialize Register Page
function initRegisterPage() {
    document.getElementById('registerForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        validateRegisterForm();
    });
}

// Load authentication state
function loadAuthState() {
    const authLinks = document.getElementById('auth-links');
    if (!authLinks) return;
    
    // Check if user is logged in (in a real app, this would check session/cookie)
    const user = sessionStorage.getItem('currentUser');
    
    if (user) {
        currentUser = JSON.parse(user);
        authLinks.innerHTML = `
            <li><a href="#" id="userName">Welcome, ${currentUser.username}</a></li>
            ${currentUser.role === 'admin' ? '<li><a href="admin.html">Admin Panel</a></li>' : ''}
            <li><a href="#" id="logoutBtn">Logout</a></li>
        `;
        document.getElementById('logoutBtn')?.addEventListener('click', logout);
    } else {
        authLinks.innerHTML = `
            <li><a href="login.html">Login</a></li>
            <li><a href="register.html">Register</a></li>
        `;
    }
}

// Load food menu from backend
async function loadFoodMenu() {
    try {
        const response = await fetch('php/foods.php?action=getAll');
        const data = await response.json();
        
        if (data.success) {
            foods = data.foods;
            displayFoods(foods);
        } else {
            showMessage('Error loading menu: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading foods:', error);
        showMessage('Error loading menu. Please try again.', 'error');
    }
}

// Display foods in the grid
function displayFoods(foodList) {
    const foodGrid = document.getElementById('foodMenu');
    if (!foodGrid) return;
    
    foodGrid.innerHTML = '';
    
    foodList.forEach(food => {
        const foodCard = document.createElement('div');
        foodCard.className = 'food-card';
        foodCard.innerHTML = `
            <img src="${food.image_url || 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400&h=250&fit=crop'}" alt="${food.name}">
            <div class="food-info">
                <h3>${food.name}</h3>
                <p class="food-description">${food.description}</p>
                <p class="food-price">$${food.price}</p>
                <p class="food-category">${food.category}</p>
                ${currentUser && currentUser.role === 'customer' ? 
                    `<button class="btn add-to-cart" data-id="${food.id}">Add to Cart</button>` : 
                    currentUser && currentUser.role === 'admin' ?
                    `<button class="btn edit-food" data-id="${food.id}">Edit</button>
                     <button class="btn delete-food" data-id="${food.id}">Delete</button>` : 
                    `<p>Please login to order</p>`
                }
            </div>
        `;
        foodGrid.appendChild(foodCard);
    });
    
    // Add event listeners for buttons
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            const foodId = this.getAttribute('data-id');
            addToCart(foodId);
        });
    });
    
    document.querySelectorAll('.edit-food').forEach(btn => {
        btn.addEventListener('click', function() {
            const foodId = this.getAttribute('data-id');
            editFood(foodId);
        });
    });
    
    document.querySelectorAll('.delete-food').forEach(btn => {
        btn.addEventListener('click', function() {
            const foodId = this.getAttribute('data-id');
            deleteFood(foodId);
        });
    });
}

// Filter and search foods
function filterFoods() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    
    let filteredFoods = foods;
    
    if (searchTerm) {
        filteredFoods = filteredFoods.filter(food => 
            food.name.toLowerCase().includes(searchTerm) ||
            food.description.toLowerCase().includes(searchTerm)
        );
    }
    
    if (category) {
        filteredFoods = filteredFoods.filter(food => food.category === category);
    }
    
    displayFoods(filteredFoods);
}

// Add item to cart
function addToCart(foodId) {
    const food = foods.find(f => f.id == foodId);
    if (!food) return;
    
    const existingItem = cart.find(item => item.id == foodId);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            ...food,
            quantity: 1
        });
    }
    
    updateCartDisplay();
    showMessage(`${food.name} added to cart!`, 'success');
}

// Update cart display
function updateCartDisplay() {
    const cartSection = document.getElementById('orderCart');
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    
    if (cart.length === 0) {
        cartSection.style.display = 'none';
        return;
    }
    
    cartSection.style.display = 'block';
    cartItems.innerHTML = '';
    
    let total = 0;
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = `
            <div>
                <h4>${item.name}</h4>
                <p>Quantity: ${item.quantity}</p>
            </div>
            <div>
                <p>$${itemTotal.toFixed(2)}</p>
                <button class="btn remove-item" data-id="${item.id}">Remove</button>
            </div>
        `;
        cartItems.appendChild(cartItem);
    });
    
    cartTotal.textContent = `Total: $${total.toFixed(2)}`;
    
    // Add event listeners for remove buttons
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            removeFromCart(itemId);
        });
    });
}

// Remove item from cart
function removeFromCart(foodId) {
    cart = cart.filter(item => item.id != foodId);
    updateCartDisplay();
    showMessage('Item removed from cart', 'success');
}

// Place order
async function placeOrder() {
    if (!currentUser) {
        showMessage('Please login to place an order', 'error');
        return;
    }
    
    if (cart.length === 0) {
        showMessage('Your cart is empty', 'error');
        return;
    }
    
    const orderData = {
        userId: currentUser.id,
        items: cart,
        total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
    };
    
    try {
        const response = await fetch('php/orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Order placed successfully!', 'success');
            cart = [];
            updateCartDisplay();
        } else {
            showMessage('Error placing order: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error placing order:', error);
        showMessage('Error placing order. Please try again.', 'error');
    }
}

// Login form validation
function validateLoginForm() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        showMessage('Please fill in all fields', 'error', 'loginMessage');
        return false;
    }
    
    // Form is valid, allow submission
    return true;
}

// Register form validation
function validateRegisterForm() {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    const messageDiv = document.getElementById('registerMessage');
    
    // Clear previous messages
    messageDiv.innerHTML = '';
    
    // Validation checks
    if (!username || !email || !password || !confirmPassword) {
        showMessage('Please fill in all fields', 'error', 'registerMessage');
        return false;
    }
    
    if (password !== confirmPassword) {
        showMessage('Passwords do not match', 'error', 'registerMessage');
        return false;
    }
    
    if (password.length < 6) {
        showMessage('Password must be at least 6 characters', 'error', 'registerMessage');
        return false;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showMessage('Please enter a valid email address', 'error', 'registerMessage');
        return false;
    }
    
    // Form is valid
    showMessage('Registration successful!', 'success', 'registerMessage');
    return true;
}

// Logout function
function logout() {
    sessionStorage.removeItem('currentUser');
    currentUser = null;
    window.location.href = 'index.html';
}

// Show message function
function showMessage(message, type, elementId = null) {
    const messageDiv = elementId ? document.getElementById(elementId) : document.createElement('div');
    
    if (!elementId) {
        messageDiv.className = `message ${type}`;
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    } else {
        messageDiv.innerHTML = `<div class="message ${type}">${message}</div>`;
        
        if (type === 'success') {
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 3000);
        }
    }
}

// Admin functions
function editFood(foodId) {
    // Redirect to admin edit page
    window.location.href = `admin.html?edit=${foodId}`;
}

async function deleteFood(foodId) {
    if (!confirm('Are you sure you want to delete this item?')) return;
    
    try {
        const response = await fetch(`php/foods.php?action=delete&id=${foodId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Food item deleted successfully', 'success');
            loadFoodMenu(); // Refresh the list
        } else {
            showMessage('Error deleting item: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error deleting food:', error);
        showMessage('Error deleting item', 'error');
    }
}