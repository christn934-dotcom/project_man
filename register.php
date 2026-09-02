<?php

session_start();

require_once "config/database.php";
require_once "send_email_notification.php";

$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| HANDLE REGISTRATION
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "member";


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        empty($full_name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must contain at least 6 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } elseif (
        $role !== "member" &&
        $role !== "project_manager"
    ) {

        $error = "Invalid account type.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | CHECK EMAIL
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {

            $error = "An account with this email already exists.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | HASH PASSWORD
            |--------------------------------------------------------------------------
            */

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /*
            |--------------------------------------------------------------------------
            | CREATE USER
            |--------------------------------------------------------------------------
            */

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users
                (
                    full_name,
                    email,
                    password,
                    role,
                    status
                )
                VALUES (?, ?, ?, ?, 'active')"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $full_name,
                $email,
                $hashed_password,
                $role
            );


            if (mysqli_stmt_execute($stmt)) {

                $success =
                    "Account created successfully. You can now log in.";

                /* Welcome email */
                $new_user_id = mysqli_insert_id($conn);
                $welcome_subject = "Welcome to PROMASY!";
                $welcome_body = "Hello $full_name,\n\nWelcome to PROMASY — Project Management System!\n\nYour account has been created successfully. You can now log in and start collaborating with your team.\n\nIf you have any questions, contact your administrator.\n\n- The PROMASY Team";
                send_user_notification_email($conn, $new_user_id, $welcome_subject, $welcome_body);

            } else {

                $error =
                    "Registration failed. Please try again.";

            }

        }

        mysqli_stmt_close($stmt);

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
        Create Account | PMS
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


<div class="login-container">


    <div class="login-card">


        <!-- HEADER -->

        <div class="login-header">

            <h1>
                Create Account
            </h1>

            <p>
                Create your Project Management System account
            </p>

        </div>


        <!-- ERROR -->

        <?php if (!empty($error)): ?>

            <div class="alert-error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- SUCCESS -->

        <?php if (!empty($success)): ?>

            <div class="alert-success">

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <form
            method="POST"
            action=""
        >


            <!-- FULL NAME -->

            <div class="form-group">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars(
                        $_POST["full_name"] ?? ""
                    ) ?>"
                    required
                    autocomplete="name"
                >

            </div>


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
                    value="<?= htmlspecialchars(
                        $_POST["email"] ?? ""
                    ) ?>"
                    required
                    autocomplete="email"
                >

            </div>


            <!-- ACCOUNT TYPE -->

            <div class="form-group">

                <label for="role">
                    Account Type
                </label>

                <select
                    id="role"
                    name="role"
                    required
                >

                    <option
                        value="member"
                        <?= (
                            ($_POST["role"] ?? "member")
                            === "member"
                        )
                            ? "selected"
                            : ""
                        ?>
                    >
                        Team Member
                    </option>


                    <option
                        value="project_manager"
                        <?= (
                            ($_POST["role"] ?? "")
                            === "project_manager"
                        )
                            ? "selected"
                            : ""
                        ?>
                    >
                        Project Manager
                    </option>

                </select>

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
                    placeholder="Create a password"
                    required
                    autocomplete="new-password"
                >

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    required
                    autocomplete="new-password"
                >

            </div>


            <!-- BUTTON -->

            <button
                type="submit"
                class="login-button"
            >
                Create Account
            </button>


        </form>


        <!-- FOOTER -->

        <div class="login-footer">

            <p>

                Already have an account?

                <a href="login.php">
                    Sign in
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