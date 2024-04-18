<!DOCTYPE html>
<html>

<head>
    <title>Chickenamor POS</title>
    <link rel="stylesheet" href="cashierDashboard.css"> <!-- Link to CSS file -->
</head>

<body>

    <!-- Top Section -->
    <div id="top-section">
        <h1>Chickenamor POS</h1>
    </div>

    <div id="container">

        <!-- Left Sidebar -->
        <div id="left-sidebar">
            <nav>
                <ul>
                    <li><button id="menu-button">Menu</button></li>
                    <li><button id="orders-button">Orders</button></li>
                    <li><button id="logout-button">Logout</button></li>
                </ul>
            </nav>
        </div>

        <!-- Middle Section -->
        <div id="middle-section">

            <!-- Category Bar -->
            <div id="category-bar">
                <!-- Hardcoded categories for demonstration; you can populate them dynamically -->
                <button onclick="fetchMenuItems('ValueMeals')">Value Meals</button>
                <button onclick="fetchMenuItems('FlavoredWings')">Flavored Wings</button>
                <button onclick="fetchMenuItems('Desserts')">Desserts</button>
                <button onclick="fetchMenuItems('Coolers')">Coolers</button>
                <button onclick="fetchMenuItems('AddOns')">Add-Ons</button>
            </div>

            <!-- Menu Item Cards Grid -->
            <div id="menu-grid">
                <?php
                // Include the PHP file with your functions
                require_once 'database_functions.php';

                // Default category
                $defaultCategory = 'ValueMeals'; // Update to match your category names

                // Fetch menu items for the default category
                $menuItems = fetchMenuItems($defaultCategory);

                // Loop through the menu items and generate HTML for each card
                foreach ($menuItems as $item) {
                    echo '<div class="menu-item-card">';
                    
                    // Display image of the item
                    echo '<img src="' . htmlspecialchars($item['image']) . '" alt="' . htmlspecialchars($item['ItemName']) . '">';
                    
                    // Display item details (name and price)
                    echo '<h3>' . htmlspecialchars($item['ItemName']) . '</h3>';
                    echo '<p>Price: $' . number_format($item['Price'], 2) . '</p>';
                    
                    // Add to Order button
                    echo '<button onclick="addToOrder(' . htmlspecialchars($item['ItemID']) . ')">Add to Order</button>';
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Right Section -->
        <div id="right-section">
            <!-- Order List -->
            <div id="order-list">
                <!-- The order list will be populated dynamically using JavaScript -->
            </div>

            <!-- Serving Type Options -->
            <div id="serving-options">
                <label for="serving-type">Serving Type:</label>
                <select id="serving-type">
                    <option value="dine-in">Dine-in</option>
                    <option value="take-out">Take-out</option>
                </select>
            </div>

            <!-- Total Order Amount and Confirm Button -->
            <div id="order-summary">
                <p>Total Order Amount: <span id="total-amount">$0.00</span></p>
                <button id="confirm-button">Confirm Order</button>
            </div>
        </div>

    </div>

    <!-- JavaScript for interactivity -->
    <script src="scripts.js"></script>

</body>

</html>