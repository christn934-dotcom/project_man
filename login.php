<?php

session_start();

require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, full_name, email, password, role, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {

            $user = mysqli_fetch_assoc($result);

            if ($user["status"] !== "active") {

                $error = "Your account is inactive.";

            } elseif (password_verify($password, $user["password"])) {

                // Prevent session fixation
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                /*
                |--------------------------------------------------------------------------
                | Redirect according to role
                |--------------------------------------------------------------------------
                */

                if ($user["role"] === "admin") {

                    header("Location: admin-dashboard.php");
                    exit;

                } elseif ($user["role"] === "project_manager") {

                    header("Location: dashboard.php");
                    exit;

                } elseif ($user["role"] === "member") {

                    header("Location: dashboard.php");
                    exit;

                } else {

                    $error = "Invalid user role.";
                }

            } else {

                $error = "Invalid email or password.";
            }

        } else {

            $error = "Invalid email or password.";
        }

        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Project Management System</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

    <div class="login-container">

        <div class="login-card">

            <div class="login-header">

                <h1>Welcome Back</h1>

                <p>
                    Sign in to your Project Management System
                </p>

            </div>

            <?php if (!empty($error)): ?>

                <div class="alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                        autocomplete="email"
                    >

                </div>

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >

                </div>

                <div class="form-options">

                    <label class="remember-me">

                        <input
                            type="checkbox"
                            name="remember"
                        >

                        <span>Remember me</span>

                    </label>

                    <a href="#">
                        Forgot password?
                    </a>

                </div>

                <button
                    type="submit"
                    class="login-button"
                >
                    Sign In
                </button>

            </form>

            <div class="login-footer">

                <p>
                    Don't have an account?
                    <a href="register.php">Create account</a>
                </p>

            </div>

        </div>

    </div>

</body>

</html>