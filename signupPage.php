<?php
// Database connection
require_once 'database_functions.php';
$conn = connectDatabase();

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate form data
    if (empty($email) || empty($username) || empty($password)) {
        $_SESSION['error'] = "all fields are required";
        header("Location: {$_SERVER['PHP_SELF']}");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "invalid email format";
        header("Location: {$_SERVER['PHP_SELF']}");
        exit();
    }

    // Check for duplicate username and email
    $sql_check = "SELECT * FROM user WHERE username = ? OR email = ?";
    $stmt_check = $conn->prepare($sql_check);
    if ($stmt_check) {
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $_SESSION['error'] = "username/email already in use";
            $stmt_check->close();
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        }
        $stmt_check->close();
    } else {
        $_SESSION['error'] = "Error preparing statement: " . $conn->error;
        header("Location: {$_SERVER['PHP_SELF']}");
        exit();
    }

    // Hash the password for secure storage
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and execute SQL statement
    $sql = "INSERT INTO user (email, username, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $email, $username, $hashed_password);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Account created successfully! Please log in.";
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        }
        //$stmt->close();
    } else {
        $_SESSION['error'] = "Error preparing statement: " . $conn->error;
        header("Location: {$_SERVER['PHP_SELF']}");
        exit();
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="initial-scale=1, width=device-width" />
    <link rel="stylesheet" href="./global.css" />
    <link rel="stylesheet" href="./signupPage.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" />
</head>
<body>
    <div class="create-account">
        <?php
        // Display error or success messages from session
        if (isset($_SESSION['error'])) {
            echo '<script>' . htmlspecialchars($_SESSION['error']) . '</script>';
            unset($_SESSION['error']);
        }

        if (isset($_SESSION['success'])) {
            echo '<script>' . htmlspecialchars($_SESSION['success']) . '</script>';
            unset($_SESSION['success']);
        }?>
        <div class="label">
            <b class="chickenamor">CHICKENAMOR</b>
            <div class="tagline">
                <div class="chicken-pa-more">CHICKEN PA MORE!</div>
            </div>
        </div>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="textfields">
            <div class="emailform">
                <b class="title">Email</b>
                <div class="email">
                    <input type="email" name="email" id="email" class="place-holder" placeholder="Enter Email" required />
                </div>
            </div>
            <div class="usernameform">
                <b class="title1">Username</b>
                <div class="username">
                    <input type="text" name="username" id="username" class="place-holder1" placeholder="Enter username" required />
                </div>
            </div>
            <div class="passwordform">
                <b class="title3">Password</b>
                <div class="password">
                    <input type="password" name="password" id="password" class="place-holder3" placeholder="Enter password" required />
                </div>
            </div>
            <div class="buttons">
                <button type="submit" class="createaccbutton" id="createAccButton">
                    <div class="create-an-account">Create Account</div>
                </button>
                <div class="loginpagebutton" id="loginPageButtonContainer">
                    <b class="create-an-account1">I already have an account</b>
                </div>
            </div>
        </form>
    </div>

    <script>
        var loginPageButtonContainer = document.getElementById("loginPageButtonContainer");
        if (loginPageButtonContainer) {
            loginPageButtonContainer.addEventListener("click", function (e) {
                window.location.href = "./LoginPage.php";
            });
        }
    </script>
</body>
</html>
