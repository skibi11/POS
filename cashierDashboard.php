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
                        $_SESSION["selectedCategory"] = 'ValueMeal';
                    } elseif (isset($_POST['FlavoredWings'])) {
                        // Call the PHP function for Flavored Wings category
                        $_SESSION["selectedCategory"] = 'FlavoredWings';
                    } elseif (isset($_POST['Desserts'])) {
                        // Call the PHP function for Desserts category
                        $_SESSION["selectedCategory"] = 'Desserts';
                    } elseif (isset($_POST['Coolers'])) {
                        // Call the PHP function for Coolers category
                        $_SESSION["selectedCategory"] = 'Coolers';
                    } elseif (isset($_POST['AddOns'])) {
                        // Call the PHP function for Add-Ons category
                        $_SESSION["selectedCategory"] = 'AddOns';
                    }
                }
                // Check if the form was submitted for adding an item to the order
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addToOrder'])) {
                    // Retrieve the item ID from the POST data
                    $itemID = htmlspecialchars($_POST['item_id'], ENT_QUOTES, 'UTF-8');
                    
                    // Call your PHP function to add the item to the order
                    addItemToOrder($itemID);

                    // Redirect to the same page without form data to avoid resubmission on refresh
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }

                // Fetch menu items for the default category
                $selectedCategory = $_SESSION['selectedCategory'] ?? '';
    if ($selectedCategory === '') {
        $menuItems = fetchMenuItems($defaultCategory);
    } else {
        $menuItems = fetchMenuItems($selectedCategory);
    }

                // Loop through the menu items and generate HTML for each card
                foreach ($menuItems as $item) {
                    echo '<div class="menu-item-card">';
                    
                    // // Display image of the item

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
                <h2>Order List</h2>
                    <ul>
                    <?php
                // Include the PHP file with your functions
                require_once 'database_functions.php';

                // Call your PHP function to get the order items
                $orderItems = fetchOrderList(); // Replace getOrderItems() with your actual function name

                // Loop through the order items and display them in the list
                foreach ($orderItems as $item) {
                    echo '<li>';
                    echo 'Item ID: ' . htmlspecialchars($item['ItemID']) . '<br>';
                    echo 'Quantity: ' . htmlspecialchars($item['Quantity']) . '<br>';
                    echo 'Subtotal: $' . number_format($item['Subtotal'], 2) . '<br>';
                    echo '<form method="post">';
                    echo '<input type="hidden" name="item_id" value="' . htmlspecialchars($item['ItemID']) . '">';
                    echo '<input type="submit" name="decrease" value="-">';
                    echo '<input type="submit" name="increase" value="+">';
                    echo '</form>';
                    ;
                }

                if (isset($_POST['increase']) || isset($_POST['decrease'])) {
                    // Retrieve the item ID from the POST data
                    $itemID = htmlspecialchars($_POST['item_id'], ENT_QUOTES, 'UTF-8');

                    // Determine the action and call the appropriate function
                    if ( isset($_POST['increase'])) {
                        // Call the function to increase the quantity
                        increaseQuantity($itemID);
                    } elseif (isset($_POST['decrease'])) {
                        // Call the function to decrease the quantity
                        decreaseQuantity($itemID);
                    }
                }
                ?>
                </ul>
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
                <p>Total Order Amount: <span id="total-amount"><?php echo fetchOrderTotalAmount() ?></span></p>
                <button id="confirm-button">Confirm Order</button>
            </div>
        </div>

    </div>

</body>
</html>