<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'chickenamorDatabase1'; // Database name provided by you

// Function to establish a database connection
function connectDatabase() {
    global $host, $username, $password, $database;
    $conn = new mysqli($host, $username, $password, $database);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

//1. Function to fetch menu items based on the selected category

function fetchMenuItems($category) {
    $conn = connectDatabase();

    $stmt = $conn->prepare("SELECT ItemID, ItemName, Price, Category, image FROM MenuItem WHERE Category = ?");
    $stmt->bind_param('s', $category);
    $stmt->execute();

    $result = $stmt->get_result();
    $menuItems = [];

    while ($row = $result->fetch_assoc()) {
        $menuItems[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $menuItems;
}

//2. Function to add an order item
function addOrderItem($orderId, $menuItemId, $quantity) {
    $conn = connectDatabase();

    // Fetch the price of the menu item
    $menuItem = fetchMenuItemById($menuItemId);
    $subtotal = $menuItem['Price'] * $quantity;

    $stmt = $conn->prepare("INSERT INTO OrderItem (OrderID, MenuItemID, Quantity, Subtotal) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iiid', $orderId, $menuItemId, $quantity, $subtotal);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

//3. Function to fetch a menu item by its ID
function fetchMenuItemById($menuItemId) {
    $conn = connectDatabase();

    $stmt = $conn->prepare("SELECT * FROM MenuItem WHERE ItemID = ?");
    $stmt->bind_param('i', $menuItemId);
    $stmt->execute();

    $result = $stmt->get_result();
    $menuItem = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $menuItem;
}

//4. Function to fetch order list items from the database
function fetchOrderList() {
    $conn = connectDatabase();

    $sql = "SELECT * FROM OrderItem JOIN MenuItem ON OrderItem.MenuItemID = MenuItem.ItemID";
    $result = $conn->query($sql);
    
    $orderList = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orderList[] = $row;
        }
    }

    $conn->close();
    return $orderList;
}

//5. Function to add an item to the order
function addItemToOrder($itemID) {
    $conn = connectDatabase();

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
        $newQuantity = currentQuantity + 1;
        updateOrderItemQuantity($orderItemID, newQuantity);
    } else {
        // Item not in order, add a new order item
        $quantity = 1;
        $subtotal = getMenuItemPrice($itemID) * quantity;

        $sql = "INSERT INTO OrderItem (MenuItemID, Quantity, Subtotal) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('idi', $itemID, quantity, subtotal);
        $stmt->execute();
    }

    $stmt->close();
    $conn->close();
}

//6. Function to update the quantity of an order item
function updateOrderItemQuantity($orderItemID, $quantity) {
    $conn = connectDatabase();

    $sql = "UPDATE OrderItem SET Quantity = ?, Subtotal = Quantity * (SELECT Price FROM MenuItem WHERE ItemID = OrderItem.MenuItemID) WHERE OrderItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', quantity, orderItemID);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

//7.  Function to remove an order item
function removeOrderItem($orderItemID) {
    $conn = connectDatabase();

    $sql = "DELETE FROM OrderItem WHERE OrderItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', orderItemID);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

//8. Function to fetch order details
function fetchOrderDetails($orderID) {
    $conn = connectDatabase();

    $sql = "SELECT * FROM `Order` WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderID);
    $stmt->execute();

    $result = $stmt->get_result();
    $orderDetails = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $orderDetails;
}

//9. Function to confirm the order with serving type and total amount
function confirmOrder($orderID, $servingType, $totalAmount) {
    $conn = connectDatabase();

    $sql = "UPDATE `Order` SET ServingType = ?, TotalAmount = ?, StatusID = (SELECT StatusID FROM OrderStatus WHERE StatusLabel = 'Confirmed') WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sdi', servingType, totalAmount, orderID);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

//10. Function to fetch serving type options from the database
function fetchServingTypeOptions() {
    $conn = connectDatabase();

    $sql = "SELECT DISTINCT ServingType FROM `Order`";
    $result = $conn->query($sql);

    $servingTypes = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $servingTypes[] = $row['ServingType'];
        }
    }

    $conn->close();
    return $servingTypes;
}

//11. Function to get the price of a menu item by its ID
function getMenuItemPrice($itemID) {
    $conn = connectDatabase();

    $sql = "SELECT Price FROM MenuItem WHERE ItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', itemID);
    $stmt->execute();

    $result = $stmt->get_result();
    $price = $result->fetch_assoc()['Price'];

    $stmt->close();
    $conn->close();

    return $price;
}

//12. Function to update the status of an order
function updateOrderStatus($orderID, $status) {
    $conn = connectDatabase();

    $sql = "UPDATE `Order` SET StatusID = (SELECT StatusID FROM OrderStatus WHERE StatusLabel = ?) WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', status, orderID);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

//13. Function to create a new order for a given user ID and serving type
function createNewOrder($userID, $servingType) {
    $conn = connectDatabase();

    $sql = "INSERT INTO `Order` (UserID, ServingType, TotalAmount, StatusID) VALUES (?, ?, 0, (SELECT StatusID FROM OrderStatus WHERE StatusLabel = 'Pending'))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', userID, servingType);
    $stmt->execute();

    $orderID = $stmt->insert_id;

    $stmt->close();
    $conn->close();

    return orderID;
}

//14. Function to fetch the history of changes for a specific order
function fetchOrderHistory($orderID) {
    $conn = connectDatabase();

    $sql = "SELECT * FROM OrderHistory JOIN OrderStatus ON OrderHistory.StatusID = OrderStatus.StatusID WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', orderID);
    $stmt->execute();

    $result = $stmt->get_result();
    $history = [];

    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    $stmt->close();
    $conn->close();

    return history;
}

//15. Function to add a notification for a specific order
function addNotification($orderID, $message) {
    $conn = connectDatabase();

    $sql = "INSERT INTO Notification (OrderID, Message, NotificationDate, StatusID) VALUES (?, ?, NOW(), (SELECT StatusID FROM OrderStatus WHERE StatusLabel = 'Pending'))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', orderID, message);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

//16. Function to fetch notifications for a specific order
function fetchNotifications($orderID) {
    $conn = connectDatabase();

    $sql = "SELECT * FROM Notification WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', orderID);
    $stmt->execute();

    $result = $stmt->get_result();
    $notifications = [];

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    $stmt->close();
    $conn->close();

    return notifications;
}

//17. Function to fetch details of a specific user
function fetchUserDetails($userID) {
    $conn = connectDatabase();

    $sql = "SELECT * FROM User WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', userID);
    $stmt->execute();

    $result = $stmt->get_result();
    $userDetails = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return userDetails;
}

//18. Function to fetch the total amount of a specific order
function fetchOrderTotalAmount($orderID) {
    $conn = connectDatabase();

    $sql = "SELECT TotalAmount FROM `Order` WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', orderID);
    $stmt->execute();

    $result = $stmt->get_result();
    $orderDetails = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $orderDetails['TotalAmount'];
}

//19. Function to fetch distinct menu categories from the database
function fetchMenuCategories() {
    $conn = connectDatabase();

    $sql = "SELECT DISTINCT Category FROM MenuItem";
    $result = $conn->query($sql);

    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['Category'];
        }
    }

    $conn->close();
    return categories;
}