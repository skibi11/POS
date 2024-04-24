<?php
// Database connection
require_once 'database_functions.php';
$conn = connectDatabase();

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate form data
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Both username and password are required.";
        header("Location: {$_SERVER['PHP_SELF']}");
        exit();
    }

    // Prepare the SQL query to find the user by username
    $sql = "SELECT * FROM user WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if user exists
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Password matches, log the user in
                $_SESSION['user_ID'] = $user['UserID'];
                $_SESSION['username'] = $user['username'];
                // Redirect the user to their account dashboard or another protected page
                header("Location: ./cashierDashboard.php");

                exit();
            } else {
                // Password does not match
                $_SESSION['error'] = "invalid username/password.";
            }
        } else {
            // No user found with the given username
            $_SESSION['error'] = "invalid username/password.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "error preparing statement: " . $conn->error;
    }

    // Redirect back to the login page with an error message
    header("Location: {$_SERVER['PHP_SELF']}");
    exit();
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
    <link rel="stylesheet" href="./loginPage.css" />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
    />
  </head>
  <body>
    <div class="login-page">
        <?php
        // Display error messages from the session
        if (isset($_SESSION['error'])) {
            $text =  htmlspecialchars($_SESSION['error']);
            echo "<script>alert('$text')</script>";
            unset($_SESSION['error']);
        }
        ?>
        <div class="login-page-inner">
            <div class="chickenamortitle-parent">
                <b class="chickenamortitle">CHICKENAMOR</b>
                <div class="chicken-pa-more-wrapper">
                    <div class="chicken-pa-more1">CHICKEN PA MORE!</div>
                </div>
            </div>
        </div>
        <section class="password-form">
            <div class="textfields-wrapper">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="textfields1">
                    <div class="usernameform1">
                        <b class="title5">Username</b>
                        <div class="username1">
                            <input class="place-holder5" name="username" id="username" placeholder="Enter username" type="text" required />
                        </div>
                    </div>
                    <div class="passwordform1">
                        <b class="title6">Password</b>
                        <div class="password1">
                            <input class="place-holder6" name="password" id="password" placeholder="Enter password" type="password" required />
                        </div>
                    </div>
                    <div class="buttons1">
                        <button type="submit" class="login" id="loginContainer">
                            <b class="create-an-account2">Login</b>
                        </button>
                        <div class="createacc" id="signupPageContainer">
                            <b class="create-an-account3">Create an Account</b>
                        </div>
                    </div>
                </form>
            </div>
            <img
              class="chickenamorpic-icon"
              loading="lazy"
              alt=""
              src="./includes/chickenamorpic@2x.png"
            />
        </section>
    </div>
    <script>
        var signupPageContainer = document.getElementById("signupPageContainer");
        if (signupPageContainer) {
            signupPageContainer.addEventListener("click", function (e) {
                window.location.href = "./signupPage.php";
            });
        }
    </script>
  </body>
</html>
