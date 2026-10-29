<?php

session_start();

require_once "db.php";

$error = "";


// Where each role goes

function dashboardFor($role) {
    return $role === "STAFF" ? "staff_dashboard.php" : "user_dashboard.php";
}


// Already logged in? Go straight to the right dashboard

if (isset($_SESSION["user_id"])) {
    header("Location: " . dashboardFor($_SESSION["role"]));
    exit;
}


// Handle the login form

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter username and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, password, role FROM users WHERE username = ?"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user["password"])) {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"]    = $user["name"];
            $_SESSION["role"]    = $user["role"];

            header("Location: " . dashboardFor($user["role"]));
            exit;

        } else {

            $error = "Invalid username or password.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Smart Queuing System</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .login-wrapper { max-width: 420px; margin: 60px auto; padding: 20px; }
        .login-wrapper input[type="text"],
        .login-wrapper input[type="password"] {
            width: 100%; padding: 14px; margin-bottom: 20px;
            border: 1px solid #ccc; border-radius: 6px; font-size: 16px;
        }
        .login-wrapper label { display: block; margin-bottom: 6px; font-weight: bold; }
        .error-msg { background: #fee2e2; color: #991b1b; padding: 12px;
                     border-radius: 6px; margin-bottom: 20px; }
    </style>

</head>

<body>

    <header>
        <h1>Smart Queuing System</h1>
        <p>Queue smarter. Wait less.</p>
    </header>

    <div class="login-wrapper">

        <section class="queue-box">

            <h2>Login</h2>

            <?php if ($error !== ""): ?>
                <div class="error-msg">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">

                <label for="username">Username</label>
                <input type="text" id="username" name="username" autofocus>

                <label for="password">Password</label>
                <input type="password" id="password" name="password">

                <button type="submit">Login</button>

            </form>

        </section>

    </div>

</body>

</html>
