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
            $params["secure"], $params["httponly"]);
    }
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

//Function to fetch the total amount of a group of orders
function fetchOrderTotalAmount() {
    $conn = connectDatabase();
    $totalQuantity = 0;
    $sql = "SELECT * FROM orderItem";
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

// Function to clear the order list
function clearOrderList() {
    // Start the session if it hasn't been started already
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear the order list stored in the session
    $_SESSION['orderList'] = [];
}
// Function to clear the order items table
function clearOrderItems() {
    $conn = connectDatabase();

    // SQL query to delete all records from the orderitem table
    $sql = "DELETE FROM orderitem"; // Corrected table name
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

// Function to generate order number
function generateOrderNumber() {
    // Connect to the database
    $conn = connectDatabase();

    // Query to get the latest OrderID and OrderNumber
    $sql = "SELECT MAX(OrderID) AS LatestOrderID, MAX(OrderNumber) AS LatestOrderNumber FROM orderr";
    $result = $conn->query($sql);

    // Check if there are any orders in the database
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $latestOrderID = $row['LatestOrderID'];
        $latestOrderNumber = $row['LatestOrderNumber'];

        // Increment the latest OrderID by 1 to generate the new OrderNumber
        if ($latestOrderNumber !== null) {
            // If there is an existing OrderNumber, increment it by 1
            $newOrderNumber = $latestOrderNumber + 1;
        } else {
            // If no OrderNumber exists, use the incremented OrderID as the OrderNumber
            $newOrderNumber = $latestOrderID + 1;
        }
    } else {
        // If no orders exist yet, start with OrderID 1
        $newOrderNumber = 1;
    }

    // Close the database connection
    $conn->close();

    return $newOrderNumber;
}

// Function to insert values order items into orderr table
function insertOrderItemsIntoOrderr($orderItems, $orderNumber, $servingType) {
    // Get additional attributes
    $orderDate = date('Y-m-d H:i:s'); // Current date and time
    $status = 'Ongoing'; // Default status
    $userID = $_SESSION['user_ID'] ?? ''; // Assuming UserID is stored in the session

    // Insert each item into orderr
    $totalAmount = 0; // Initialize totalAmount
    foreach ($orderItems as $item) {
        $itemID = $item['ItemID'];
        $quantity = $item['Quantity'];
        $subtotal = $item['Subtotal'];

        // Insert into orderr
        addOrderItem($orderDate, $servingType, $status, $userID, $orderNumber, $itemID, $quantity, $subtotal);

        // Update total amount
        $totalAmount += $subtotal;
    }

    // Insert total amount into payment table
    insertTotalAmountIntoPayment($totalAmount, $orderNumber);
}

// Function to insert an item into orderr
function addOrderItem($orderDate, $servingType, $status, $userID, $orderNumber, $itemID, $quantity, $subtotal) {
    $conn = connectDatabase();

    $sql = "INSERT INTO orderr (OrderDate, ServingType, Status, UserID, OrderNumber, ItemID, Quantity, Subtotal) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sisiiiid', $orderDate, $servingType, $status, $userID, $orderNumber, $itemID, $quantity, $subtotal);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}


// Function to insert total amount into payment table
function insertTotalAmountIntoPayment($totalAmount, $orderNumber) {
    $conn = connectDatabase();

    // Get the current date and time
    $paymentDate = date('Y-m-d H:i:s');

    // Check if the order number already exists in the payment table
    $sql = "SELECT OrderNumber FROM payment WHERE OrderNumber = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $orderNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the order number exists, update the existing row
    if ($result->num_rows > 0) {
        $sql = "UPDATE payment SET TotalAmount = ?, PaymentDate = ? WHERE OrderNumber = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('dsi', $totalAmount, $paymentDate, $orderNumber);
        $stmt->execute();
    } else {
        // If the order number doesn't exist, insert a new row
        $sql = "INSERT INTO payment (TotalAmount, PaymentDate, OrderNumber) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('dsi', $totalAmount, $paymentDate, $orderNumber);
        $stmt->execute();
    }

    // Clear the order list and orderItems table
    clearOrderList();
    clearOrderItems();
    header('Location: cashierDashboard.php');
    
    // Generate a new OrderNumber for the next order
    $newOrderNumber = generateOrderNumber();
    
    $stmt->close();
    $conn->close();
    return $newOrderNumber;
}