<?php
$host = 'your_host';
$username = 'your_username';
$password = 'your_password';
$database = 'chickenamorDatabase';

// 0. Function to establish a database connection
function connectDatabase() {
    global $host, $username, $password, $database;
    $conn = new mysqli($host, $username, $password, $database);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// 1. Function to fetch menu items based on the selected category
function fetchMenuItems($category) {
    $conn = connectDatabase();

    // Prepare and execute the query to fetch menu items by category
    $stmt = $conn->prepare("SELECT ItemID, ItemName, Price FROM MenuItem WHERE Category = ?");
    $stmt->bind_param('s', $category);
    $stmt->execute();

    // Fetch the results
    $result = $stmt->get_result();
    $menuItems = [];

    while ($row = $result->fetch_assoc()) {
        $menuItems[] = $row;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    return $menuItems;
}

// 2. Function to add an order item
function addOrderItem($orderId, $menuItemId, $quantity) {
    $conn = connectDatabase();
    $stmt = $conn->prepare("INSERT INTO OrderItem (OrderID, MenuItemID, Quantity, Subtotal) VALUES (?, ?, ?, ?)");
    
    // Calculate the subtotal
    $menuItem = fetchMenuItemById($menuItemId);
    $subtotal = $menuItem['Price'] * $quantity;
    
    $stmt->bind_param('iiid', $orderId, $menuItemId, $quantity, $subtotal);
    $stmt->execute();
    
    // Close the statement and connection
    $stmt->close();
    $conn->close();
}

// 3. Function to fetch a menu item by its ID
function fetchMenuItemById($menuItemId) {
    $conn = connectDatabase();
    $stmt = $conn->prepare("SELECT * FROM MenuItem WHERE ItemID = ?");
    $stmt->bind_param('i', $menuItemId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $menuItem = $result->fetch_assoc();
    
    // Close the statement and connection
    $stmt->close();
    $conn->close();
    
    return $menuItem;
}

// 4. Fetch order list items from the database
function fetchOrderList() {
    global $conn;

    $sql = "SELECT * FROM OrderItem JOIN MenuItem ON OrderItem.MenuItemID = MenuItem.ItemID";
    $result = $conn->query($sql);
    
    $orderList = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orderList[] = $row;
        }
    }
    
    return $orderList;
}

// 5. Add an item to the order
function addItemToOrder($itemID) {
    global $conn;
    
    // Check if the item already exists in the order
    $sql = "SELECT * FROM OrderItem WHERE MenuItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $itemID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Item already in order, increase the quantity
        $orderItem = $result->fetch_assoc();
        $orderItemID = $orderItem['OrderItemID'];
        $currentQuantity = $orderItem['Quantity'];
        $newQuantity = $currentQuantity + 1;
        updateOrderItemQuantity($orderItemID, $newQuantity);
    } else {
        // Item not in order, add a new order item
        $sql = "INSERT INTO OrderItem (MenuItemID, Quantity, Subtotal) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $quantity = 1;
        $subtotal = getMenuItemPrice($itemID) * $quantity;
        $stmt->bind_param('idi', $itemID, $quantity, $subtotal);
        $stmt->execute();
    }
}

// 6. Update the quantity of an order item
function updateOrderItemQuantity($orderItemID, $quantity) {
    global $conn;
    
    // Update the quantity and subtotal
    $sql = "UPDATE OrderItem SET Quantity = ?, Subtotal = Quantity * (SELECT Price FROM MenuItem WHERE ItemID = OrderItem.MenuItemID) WHERE OrderItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $quantity, $orderItemID);
    $stmt->execute();
}

// 7. Remove an order item
function removeOrderItem($orderItemID) {
    global $conn;

    $sql = "DELETE FROM OrderItem WHERE OrderItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderItemID);
    $stmt->execute();
}

// 8. Fetch order details (e.g., total amount, serving type, and list of items)
function fetchOrderDetails($orderID) {
    global $conn;

    // Fetch order details from the Order table
    $sql = "SELECT * FROM Order WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderID);
    $stmt->execute();
    $orderDetails = $stmt->get_result()->fetch_assoc();
    
    return $orderDetails;
}

// 9. Confirm the order with serving type and total amount
function confirmOrder($orderID, $servingType, $totalAmount) {
    global $conn;
    
    // Update order with serving type and total amount
    $sql = "UPDATE Order SET ServingType = ?, TotalAmount = ?, StatusID = (SELECT StatusID FROM OrderStatus WHERE StatusLabel = 'Confirmed') WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sdi', $servingType, $totalAmount, $orderID);
    $stmt->execute();
}

// 10. Fetch serving type options from the database
function fetchServingTypeOptions() {
    global $conn;

    $sql = "SELECT DISTINCT ServingType FROM Order";
    $result = $conn->query($sql);
    
    $servingTypes = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $servingTypes[] = $row['ServingType'];
        }
    }

    return $servingTypes;
}

// 11. Helper function to get the price of a menu item by its ID
function getMenuItemPrice($itemID) {
    global $conn;

    $sql = "SELECT Price FROM MenuItem WHERE ItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $itemID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['Price'];
}

// 12. Update the status of an order
function updateOrderStatus($orderID, $status) {
    global $conn;

    // Update order status
    $sql = "UPDATE Order SET StatusID = (SELECT StatusID FROM OrderStatus WHERE StatusLabel = ?) WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $status, $orderID);
    $stmt->execute();
}

// 13. Create a new order for a given user ID and serving type
function createNewOrder($userID, $servingType) {
    global $conn;

    // Create a new order
    $sql = "INSERT INTO Order (UserID, ServingType, TotalAmount, StatusID) VALUES (?, ?, 0, (SELECT StatusID FROM OrderStatus WHERE StatusLabel = 'Pending'))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $userID, $servingType);
    $stmt->execute();
    
    // Return the last inserted Order ID
    return $stmt->insert_id;
}

// 14. Fetch the history of changes for a specific order
function fetchOrderHistory($orderID) {
    global $conn;

    $sql = "SELECT * FROM OrderHistory JOIN OrderStatus ON OrderHistory.StatusID = OrderStatus.StatusID WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderID);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $history = [];
    
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    return $history;
}

// 15. Add a notification for a specific order
function addNotification($orderID, $message) {
    global $conn;

    // Add a notification
    $sql = "INSERT INTO Notification (OrderID, Message, NotificationDate, StatusID) VALUES (?, ?, NOW(), (SELECT StatusID FROM OrderStatus WHERE StatusLabel = 'Pending'))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $orderID, $message);
    $stmt->execute();
}

// 16. Fetch notifications for a specific order
function fetchNotifications($orderID) {
    global $conn;

    $sql = "SELECT * FROM Notification WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderID);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $notifications = [];
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

// 17. Fetch details of a specific user
function fetchUserDetails($userID) {
    global $conn;

    $sql = "SELECT * FROM User WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userID);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $userDetails = $result->fetch_assoc();
    
    return $userDetails;
}

// 18. Fetch the total amount of a specific order
function fetchOrderTotalAmount($orderID) {
    global $conn;

    $sql = "SELECT TotalAmount FROM Order WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderID);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $orderDetails = $result->fetch_assoc();
    
    return $orderDetails['TotalAmount'];
}

// 19. Fetch distinct menu categories from the database
function fetchMenuCategories() {
    global $conn;

    $sql = "SELECT DISTINCT Category FROM MenuItem";
    $result = $conn->query($sql);
    
    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['Category'];
        }
    }
}