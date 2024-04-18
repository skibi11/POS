<!DOCTYPE html>
<html>

<head>
    <title>Chickenamor POS</title>
    <link rel="stylesheet" href="cashierDashboard.css"> <!-- Link to CSS file -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Include jQuery -->
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
                    <li><button id="menu-button" onclick="showMenu()">Menu</button></li>
                    <li><button id="orders-button" onclick="showOrders()">Orders</button></li>
                    <li><button id="logout-button" onclick="showLogout()">Logout</button></li>
                </ul>
            </nav>
        </div>

        <!-- Middle Section -->
        <div id="middle-section">

            <!-- Category Bar -->
            <div id="category-bar">
                <button onclick="fetchMenuItems('ValueMeals')">Value Meals</button>
                <button onclick="fetchMenuItems('FlavoredWings')">Flavored Wings</button>
                <button onclick="fetchMenuItems('Desserts')">Desserts</button>
                <button onclick="fetchMenuItems('Coolers')">Coolers</button>
                <button onclick="fetchMenuItems('AddOns')">Add-Ons</button>
            </div>

            <!-- Menu Item Cards Grid -->
            <div id="menu-grid">
                <!-- This will be populated dynamically by JavaScript -->
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
                <button id="confirm-button" onclick="confirmOrder()">Confirm Order</button>
            </div>
        </div>

    </div>

    <!-- JavaScript for interactivity -->
    <script>
        // Initialize variables to keep track of order items and total amount
        let orderItems = [];
        let totalAmount = 0;

        // Function to fetch menu items based on the selected category
        function fetchMenuItems(category) {
            $.ajax({
                url: 'fetch_menu_items.php',
                method: 'POST',
                data: { category: category },
                success: function (response) {
                    const menuItems = JSON.parse(response);
                    const menuGrid = document.getElementById('menu-grid');
                    menuGrid.innerHTML = '';

                    menuItems.forEach(item => {
                        const card = document.createElement('div');
                        card.className = 'menu-item-card';

                        const img = document.createElement('img');
                        img.src = item.image;
                        img.alt = item.ItemName;

                        const name = document.createElement('h3');
                        name.textContent = item.ItemName;

                        const price = document.createElement('p');
                        price.textContent = `Price: $${item.Price.toFixed(2)}`;

                        const button = document.createElement('button');
                        button.textContent = 'Add to Order';
                        button.onclick = function() {
                            addToOrder(item.ItemID);
                        };

                        card.appendChild(img);
                        card.appendChild(name);
                        card.appendChild(price);
                        card.appendChild(button);

                        menuGrid.appendChild(card);
                    });
                }
            });
        }

        // Function to add a menu item to the order list
        function addToOrder(menuItemID) {
            // Check if the item already exists in the order
            let orderItem = orderItems.find(item => item.MenuItemID === menuItemID);

            if (orderItem) {
                // If the item exists, increase the quantity
                orderItem.Quantity++;
            } else {
                // If the item does not exist, add a new item to the order
                orderItem = {
                    MenuItemID: menuItemID,
                    Quantity: 1
                };
                orderItems.push(orderItem);
            }

            updateOrderList();
        }

        // Function to update the order list and calculate the total amount
        function updateOrderList() {
            const orderList = document.getElementById('order-list');
            orderList.innerHTML = ''; // Clear the existing order list

            totalAmount = 0;

            orderItems.forEach(orderItem => {
                $.ajax({
                    url: 'fetch_menu_item.php',
                    method: 'POST',
                    data: { ItemID: orderItem.MenuItemID },
                    async: false,
                    success: function(response) {
                        const menuItem = JSON.parse(response);
                        const orderRow = document.createElement('div');
                        orderRow.className = 'order-item-row';

                        const name = document.createElement('p');
                        name.textContent = menuItem.ItemName;

                        const quantity = document.createElement('p');
                        quantity.textContent = `Quantity: ${orderItem.Quantity}`;

                        // Create plus and minus buttons for updating quantity
                        const plusButton = document.createElement('button');
                        plusButton.textContent = '+';
                        plusButton.onclick = function() {
                            updateOrderItemQuantity(orderItem.MenuItemID, orderItem.Quantity + 1);
                        };

                        const minusButton = document.createElement('button');
                        minusButton.textContent = '-';
                        minusButton.onclick = function() {
                            updateOrderItemQuantity(orderItem.MenuItemID, orderItem.Quantity - 1);
                        };

                        // Create 'x' button for removing order item
                        const removeButton = document.createElement('button');
                        removeButton.textContent = 'x';
                        removeButton.onclick = function() {
                            removeOrderItem(orderItem.MenuItemID);
                        };

                        orderRow.appendChild(name);
                        orderRow.appendChild(quantity);
                        orderRow.appendChild(plusButton);
                        orderRow.appendChild(minusButton);
                        orderRow.appendChild(removeButton);

                        orderList.appendChild(orderRow);

                        // Calculate the subtotal for this order item
                        const subtotal = menuItem.Price * orderItem.Quantity;
                        totalAmount += subtotal;
                    }
                });
            });

            // Update the total order amount
            const totalAmountElement = document.getElementById('total-amount');
            totalAmountElement.textContent = `$${totalAmount.toFixed(2)}`;
        }

        // Function to update the quantity of an order item
        function updateOrderItemQuantity(menuItemID, newQuantity) {
            // Find the order item in the order list
            const orderItem = orderItems.find(item => item.MenuItemID === menuItemID);

            // Update the quantity if it's greater than zero
            if (newQuantity > 0) {
                orderItem.Quantity = newQuantity;
            } else {
                // Remove the order item if the quantity is zero or less
                orderItems = orderItems.filter(item => item.MenuItemID !== menuItemID);
            }

            updateOrderList();
        }

        // Function to remove an order item
        function removeOrderItem(menuItemID) {
            // Remove the order item from the list
            orderItems = orderItems.filter(item => item.MenuItemID !== menuItemID);

            updateOrderList();
        }

        // Function to confirm the order
        function confirmOrder() {
            const servingTypeElement = document.getElementById('serving-type');
            const servingType = servingTypeElement.value;

            // Send the order details to the server
            $.ajax({
                url: 'confirm_order.php',
                method: 'POST',
                data: {
                    orderItems: JSON.stringify(orderItems),
                    servingType: servingType,
                    totalAmount: totalAmount
                },
                success: function(response) {
                    alert('Order confirmed!');
                    // Reset the order list and total amount
                    orderItems = [];
                    totalAmount = 0;
                    updateOrderList();
                }
            });
        }

        // Function to show the menu (you can define specific behavior here if needed)
        function showMenu() {
            // This is a placeholder function, add your implementation here
        }

        // Function to show orders (you can define specific behavior here if needed)
        function showOrders() {
            // This is a placeholder function, add your implementation here
        }

        // Initialize the menu items when the page loads
        fetchMenuItems('ValueMeals');
    </script>

</body>

</html>
