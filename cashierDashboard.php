<!DOCTYPE html>
<html>

<head>
    <title>Chickenamor POS</title>
    <link rel="stylesheet" href="cashierDashboard.css"> <!-- Link to CSS file -->
    <script src="scripts.js"></script> <!-- Link to your JavaScript file -->
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

            <!-- Menu Item Cards Grid -->
            <div id="menu-grid">
            <?php
                // Include the PHP file with your functions
                require_once 'database_functions.php';

                // Default category
                $defaultCategory = 'ValueMeal';
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (isset($_POST['ValueMeal'])) {
                        // Call the PHP function for Value Meals category
                        $defaultCategory = 'ValueMeal';
                    } elseif (isset($_POST['FlavoredWings'])) {
                        // Call the PHP function for Flavored Wings category
                        $defaultCategory = 'FlavoredWings';
                    } elseif (isset($_POST['Desserts'])) {
                        // Call the PHP function for Desserts category
                        $defaultCategory = 'Desserts';
                    } elseif (isset($_POST['Coolers'])) {
                        // Call the PHP function for Coolers category
                        $defaultCategory = 'Coolers';
                    } elseif (isset($_POST['AddOns'])) {
                        // Call the PHP function for Add-Ons category
                        $defaultCategory = 'AddOns';
                    }
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addToOrder'])) {
                    // Retrieve the item ID from the POST data
                    $itemID = htmlspecialchars($_POST['item_id'], ENT_QUOTES, 'UTF-8');
                    
                    // Call your PHP function to add the item to the order
                    addItemToOrder($itemID);
                }

                // Fetch menu items for the default category
                $menuItems = fetchMenuItems($defaultCategory);

                // Loop through the menu items and generate HTML for each card
                foreach ($menuItems as $item) {
                    echo '<div class="menu-item-card">';
                    
                    // // Display image of the item
                    // echo '<img src="' . htmlspecialchars($item['Image']) . '" alt="' . htmlspecialchars($item['ItemName']) . '">';

                    // Use the null coalescing operator (??) to provide a default image URL if 'Image' key is missing
                    $imageUrl = $item['Image'] ?? 'path_to_default_image.jpg';
                    echo '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($item['ItemName']) . '">';
                    
                    // Display item details (name and price)
                    echo '<h3>' . htmlspecialchars($item['ItemName']) . '</h3>';
                    echo '<p>Price: $' . number_format($item['Price'], 2) . '</p>';
                    
                    // Add to Order button
                    // Add to Order button in a form
                    echo '<form method="post">';
                    echo '<input type="hidden" name="item_id" value="' . htmlspecialchars($item['ItemID']) . '">';
                    echo '<input type="submit" name="addToOrder" value="Add to Order">';
                    echo '</form>';
                    
                    echo '</div>';
                }
                ?>
            </div>
             <!-- Category Bar -->
             <div id="category-bar">
                <form method="post" id="category-bar">
                    <input type="submit" name="ValueMeal" value="Value Meals" />
                    <input type="submit" name="FlavoredWings" value="Flavored Wings" />
                    <input type="submit" name="Desserts" value="Desserts" />
                    <input type="submit" name="Coolers" value="Coolers" />
                    <input type="submit" name="AddOns" value="Add-Ons" />
                </form>
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

</body>
</html>
