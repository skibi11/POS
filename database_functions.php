<?php
session_start();
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'chickenamordatabase1'; // Database name

//Function to establish a database connection
function connectDatabase() {
    global $host, $username, $password, $database;
    $conn = new mysqli($host, $username, $password, $database);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

//Function to fetch menu items based on the selected category
function fetchMenuItems($category) {
    $conn = connectDatabase();

    $stmt = $conn->prepare("SELECT * FROM menuitem WHERE Category = ?");
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

//Function to add an order item to order
function addOrderItem($orderID, $menuItemID, $quantity) {
    $conn = connectDatabase();

    // Fetch the price of the menu item
    $menuItem = fetchMenuItemById($menuItemID);
    $subtotal = $menuItem['Price'] * $quantity;

    $stmt = $conn->prepare("INSERT INTO orderitem (OrderID, ItemID, Quantity, Subtotal) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iiid', $orderID, $menuItemID, $quantity, $subtotal);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}
//Function to add an item to the order list
function addItemToOrder($menuItemID) {
    $conn = connectDatabase();

    // Check if the item already exists in the order
    $sql = "SELECT * FROM orderitem WHERE ItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $menuItemID);
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
        $quantity = 1;
        $subtotal = getMenuItemPrice($menuItemID) * $quantity;

        $sql = "INSERT INTO orderitem (ItemID, Quantity, Subtotal) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('idi', $menuItemID, $quantity, $subtotal);
        $stmt->execute();
    }

    $stmt->close();
    $conn->close();
}

//Function to fetch a menu item by its ID
function fetchMenuItemById($menuItemID) {
    $conn = connectDatabase();

    $stmt = $conn->prepare("SELECT * FROM menuitem WHERE ItemID = ?");
    $stmt->bind_param('i', $menuItemID);
    $stmt->execute();

    $result = $stmt->get_result();
    $menuItem = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $menuItem;
}

//Function to fetch order list items from the database
function fetchOrderList() {
    $conn = connectDatabase();

    $sql = "SELECT * FROM orderitem JOIN menuitem ON orderitem.ItemID = menuitem.ItemID";
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

//Function to update the quantity of an order item
function updateOrderItemQuantity($orderItemID, $quantity) {
    $conn = connectDatabase();

    $sql = "UPDATE orderitem SET Quantity = ?, Subtotal = Quantity * (SELECT Price FROM menuitem WHERE ItemID = orderitem.ItemID) WHERE OrderItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $quantity, $orderItemID);
    $stmt->execute();

    header("Location: cashierDashboard.php");
    $stmt->close();
    $conn->close();
}

function increaseQuantity($itemID) {
    $conn = connectDatabase();

    // Fetch the current quantity of the item in the order
    $sql = "SELECT OrderItemID, Quantity FROM orderitem WHERE ItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $itemID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $orderItemID = $row['OrderItemID'];
        $currentQuantity = $row['Quantity'];

        // Increase the quantity by 1
        $newQuantity = $currentQuantity + 1;

        // Update the order item quantity and subtotal
        updateOrderItemQuantity($orderItemID, $newQuantity);
    }

    $stmt->close();
    $conn->close();
}

// Function to decrease the quantity of an order item
function decreaseQuantity($itemID) {
    $conn = connectDatabase();

    // Fetch the current quantity of the item in the order
    $sql = "SELECT OrderItemID, Quantity FROM orderitem WHERE ItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $itemID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $orderItemID = $row['OrderItemID'];
        $currentQuantity = $row['Quantity'];

        // Decrease the quantity by 1
        $newQuantity = $currentQuantity - 1;

        if ($newQuantity > 0) {
            // Update the order item quantity and subtotal if quantity is still positive
            updateOrderItemQuantity($orderItemID, $newQuantity);
        } else {
            // If the quantity is zero or negative, remove the order item
            removeOrderItem($orderItemID);
        }
    }
    header("Location: cashierDashboard.php");
    $stmt->close();
    $conn->close();
}

//Function to remove an order item
function removeOrderItem($orderItemID) {
    $conn = connectDatabase();

    $sql = "DELETE FROM orderitem WHERE OrderItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderItemID);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

// Function to get the price of a menu item by its ID
function getMenuItemPrice($menuItemID) {
    $conn = connectDatabase();

    $sql = "SELECT Price FROM menuitem WHERE ItemID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $menuItemID);
    $stmt->execute();

    $result = $stmt->get_result();
    $price = $result->fetch_assoc()['Price'];

    $stmt->close();
    $conn->close();

    return $price;
}

//Function to fetch order details
function fetchOrderDetails($orderID) {
    $conn = connectDatabase();

    $sql = "SELECT * FROM `orderr` WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderID);
    $stmt->execute();

    $result = $stmt->get_result();
    $orderDetails = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $orderDetails;
}

//Function to confirm the order with serving type and total amount
function confirmOrder($orderID, $servingType, $totalAmount) {
    $conn = connectDatabase();

    $sql = "UPDATE `orderr` SET ServingType = ?, TotalAmount = ?, StatusID = (SELECT StatusID FROM orderstatus WHERE StatusLabel = 'Confirmed') WHERE OrderID = ?";
    $stmt=$conn->prepare($sql);
    $stmt->bind_param('sdi', $servingType, $totalAmount, $orderID);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

// Function to fetch serving type options from the database
function fetchServingTypeOptions() {
    $conn = connectDatabase();

    $sql = "SELECT DISTINCT ServingType FROM `orderr`";
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

// Function to update the status of an order
function updateOrderStatus($orderID, $status) {
    $conn = connectDatabase();

    $sql = "UPDATE `orderr` SET StatusID = (SELECT StatusID FROM orderstatus WHERE StatusLabel = ?) WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $status, $orderID);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

// Function to create a new order for a given user ID and serving type
function createNewOrder($userID, $servingType) {
    $conn = connectDatabase();

    $sql = "INSERT INTO `orderr` (UserID, ServingType, TotalAmount, StatusID) VALUES (?, ?, 0, (SELECT StatusID FROM orderstatus WHERE StatusLabel = 'Pending'))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $userID, $servingType);
    $stmt->execute();

    $orderID = $stmt->insert_id;

    $stmt->close();
    $conn->close();

    return $orderID;
}

// Function to fetch the history of changes for a specific order
function fetchOrderHistory($orderID) {
    $conn = connectDatabase();

    $sql = "SELECT * FROM orderhistory JOIN orderstatus ON orderhistory.StatusID = orderstatus.StatusID WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderID);
    $stmt->execute();

    $result = $stmt->get_result();
    $history = [];

    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $history;
}

//Function to add a notification for a specific order
function addNotification($orderID, $message) {
    $conn = connectDatabase();

    $sql = "INSERT INTO `notification` (OrderID, Message, NotificationDate, StatusID) VALUES (?, ?, NOW(), (SELECT StatusID FROM orderstatus WHERE StatusLabel = 'Pending'))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $orderID, $message);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

//Function to fetch notifications for a specific order
function fetchNotifications($orderID) {
    $conn = connectDatabase();

    $sql = "SELECT * FROM `notification` WHERE OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderID);
    $stmt->execute();

    $result = $stmt->get_result();
    $notifications = [];

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $notifications;
}

//Function to fetch details of a specific user
function fetchUserDetails($userID) {
    $conn = connectDatabase();

    $sql = "SELECT * FROM user WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userID);
    $stmt->execute();

    $result = $stmt->get_result();
    $userDetails = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $userDetails;
}

//Function to fetch the total amount of a group of orders
function fetchOrderTotalAmount() {
    $conn = connectDatabase();
    $totalQuantity = 0;
    $sql = "SELECT * FROM `orderItem`";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($orderDetails = $result->fetch_assoc()) {
        $totalQuantity += $orderDetails['Subtotal'];
    }
    $stmt->close();
    $conn->close();

    return $totalQuantity;
}

// Function to log out the user
function logout() {
    // Start the session if it hasn't been started already
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Clear all session data
    $_SESSION = array();

    //Clear the session cookies
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }


    // Destroy the session
    session_destroy();

    // Redirect the user to login page 
    header("Location: loginPage.php"); 
    exit(); 
}

//Function to fetch distinct menu categories from the database
function fetchMenuCategories() {
    $conn = connectDatabase();

    $sql = "SELECT DISTINCT Category FROM menuitem";
    $result = $conn->query($sql);

    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['Category'];
        }
    }

    $conn->close();
    return $categories;
}
