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

        if (!$stmt) {

            $error = "Database error. Please try again.";

        } else {

            mysqli_stmt_bind_param($stmt, "s", $email);

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);


            if (mysqli_num_rows($result) === 1) {

                $user = mysqli_fetch_assoc($result);


                /*
                |--------------------------------------------------------------------------
                | CHECK ACCOUNT STATUS
                |--------------------------------------------------------------------------
                */

                if ($user["status"] !== "active") {

                    $error = "Your account is inactive.";

                }


                /*
                |--------------------------------------------------------------------------
                | VERIFY PASSWORD
                |--------------------------------------------------------------------------
                */

                elseif (password_verify(
                    $password,
                    $user["password"]
                )) {


                    /*
                    |--------------------------------------------------------------------------
                    | PREVENT SESSION FIXATION
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(true);


                    /*
                    |--------------------------------------------------------------------------
                    | STORE USER SESSION
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION["user_id"] = $user["id"];

                    $_SESSION["full_name"] = $user["full_name"];

                    $_SESSION["email"] = $user["email"];

                    $_SESSION["role"] = $user["role"];


                    /*
                    |--------------------------------------------------------------------------
                    | REMEMBER ME
                    |--------------------------------------------------------------------------
                    */

                    $remember = !empty($_POST["remember"]);

                    if ($remember) {

                        /* Check if cookie consent was given */
                        $consent = $_COOKIE["cookie_consent"] ?? "";

                        if ($consent === "accepted") {

                            /* Delete any existing tokens for this user */
                            $del = mysqli_prepare($conn, "DELETE FROM remember_me WHERE user_id = ?");
                            mysqli_stmt_bind_param($del, "i", $user["id"]);
                            mysqli_stmt_execute($del);
                            mysqli_stmt_close($del);

                            /* Generate secure token */
                            $raw_token = bin2hex(random_bytes(32));
                            $hashed_token = hash("sha256", $raw_token);

                            /* Store hashed token in database */
                            $insert = mysqli_prepare(
                                $conn,
                                "INSERT INTO remember_me (user_id, token, expires_at)
                                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 14 DAY))"
                            );
                            mysqli_stmt_bind_param($insert, "is", $user["id"], $hashed_token);
                            mysqli_stmt_execute($insert);
                            mysqli_stmt_close($insert);

                            /* Set cookie with raw token (14 days) */
                            setcookie(
                                "remember_me",
                                $raw_token,
                                time() + (14 * 24 * 60 * 60),
                                "/",
                                "",
                                false,
                                true  /* httpOnly */
                            );

                        }

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | ROLE-BASED REDIRECTION
                    |--------------------------------------------------------------------------
                    */


                    if ($user["role"] === "admin") {

                        header(
                            "Location: admin-dashboard.php"
                        );

                        exit;

                    }


                    elseif ($user["role"] === "project_manager") {

                        header(
                            "Location: manager-dashboard.php"
                        );

                        exit;

                    }


                    elseif ($user["role"] === "member") {

                        header(
                            "Location: member-dashboard.php"
                        );

                        exit;

                    }


                    else {

                        $error = "Invalid user role.";

                    }

                }


                else {

                    $error = "Invalid email or password.";

                }

            }


            else {

                $error = "Invalid email or password.";

            }


            mysqli_stmt_close($stmt);

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login | Project Management System
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


<div class="login-container">


    <div class="login-card">


        <!-- LOGIN HEADER -->

        <div class="login-header">

            <h1>
                Welcome Back
            </h1>

            <p>
                Sign in to your Project Management System
            </p>

        </div>



        <!-- ERROR MESSAGE -->

        <?php if (!empty($error)): ?>

            <div class="alert-error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>



        <!-- LOGIN FORM -->

        <form
            method="POST"
            action=""
        >


            <!-- EMAIL -->

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



            <!-- PASSWORD -->

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



            <!-- OPTIONS -->

            <div class="form-options">


                <label class="remember-me">

                    <input
                        type="checkbox"
                        name="remember"
                    >

                    <span>
                        Remember me
                    </span>

                </label>


                <a href="#">
                    Forgot password?
                </a>


            </div>



            <!-- LOGIN BUTTON -->

            <button
                type="submit"
                class="login-button"
            >
                Sign In
            </button>


        </form>



        <!-- FOOTER -->

        <div class="login-footer">

            <p>

                Don't have an account?

                <a href="register.php">
                    Create account
                </a>

            </p>

        </div>


    </div>


</div>


<?php include "cookie_consent.php"; ?>
<?php include "theme_toggle_floating.php"; ?>
<script src="dark_mode.php"></script>
</body>

</html>