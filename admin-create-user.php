<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
*/

require_once "auth_check.php";
require_once "avatar_helper.php";;
require_once "send_email_notification.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit;
}


$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "member";


    /*
    |--------------------------------------------------------------------------
    | Validate Name
    |--------------------------------------------------------------------------
    */

    if ($full_name === "") {

        $error = "Full name is required.";

    }


    /*
    |--------------------------------------------------------------------------
    | Validate Email
    |--------------------------------------------------------------------------
    */

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }


    /*
    |--------------------------------------------------------------------------
    | Validate Password
    |--------------------------------------------------------------------------
    */

    elseif (strlen($password) < 6) {

        $error = "Password must contain at least 6 characters.";

    }


    /*
    |--------------------------------------------------------------------------
    | Validate Role
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | Admin cannot be created from this page.
    |
    */

    elseif (
        $role !== "project_manager"
        &&
        $role !== "member"
    ) {

        $error = "Invalid user role.";

    }


    /*
    |--------------------------------------------------------------------------
    | Check Existing Email
    |--------------------------------------------------------------------------
    */

    else {

        $check = mysqli_prepare(
            $conn,
            "
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
            "
        );


        mysqli_stmt_bind_param(
            $check,
            "s",
            $email
        );


        mysqli_stmt_execute($check);


        $result = mysqli_stmt_get_result($check);


        if (mysqli_num_rows($result) > 0) {

            $error = "A user with this email already exists.";

        }


        mysqli_stmt_close($check);


        /*
        |--------------------------------------------------------------------------
        | Create User
        |--------------------------------------------------------------------------
        */

        if ($error === "") {

            $hashed_password =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $insert = mysqli_prepare(
                $conn,
                "
                INSERT INTO users
                (
                    full_name,
                    email,
                    password,
                    role,
                        profile_image,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    'active'
                )
                "
            );


            mysqli_stmt_bind_param(
                $insert,
                "ssss",
                $full_name,
                $email,
                $hashed_password,
                $role
            );


            if (mysqli_stmt_execute($insert)) {

                $success =
                    "User created successfully.";

                /*
                | Clear form values
                */

                /* Activity log */
                $actor_id = (int) $_SESSION["user_id"];
                $log_desc = "Created user: " . $full_name . " (" . $email . ")";
                $log_action = "user_created";
                $lstmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
                if ($lstmt) {
                    mysqli_stmt_bind_param($lstmt, "iss", $actor_id, $log_action, $log_desc);
                    mysqli_stmt_execute($lstmt);
                    mysqli_stmt_close($lstmt);
                }

                /* Welcome email to new user */
                $new_user_id = mysqli_insert_id($conn);
                $welcome_subject = "Welcome to PROMASY!";
                $welcome_body = "Hello $full_name,\n\nWelcome to PROMASY — Project Management System!\n\nYour account has been created by an administrator. You can now log in and start collaborating with your team.\n\n- The PROMASY Team";
                send_user_notification_email($conn, $new_user_id, $welcome_subject, $welcome_body);

                $full_name = "";
                $email = "";

            } else {

                $error =
                    "Unable to create user: "
                    . mysqli_error($conn);

            }


            mysqli_stmt_close($insert);

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
        Add User | PMS
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark'){document.body.classList.add('dark');document.body.classList.remove('light')}else if(t==='light'){document.body.classList.add('light');document.body.classList.remove('dark')}})();
</script>

<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <div class="logo-icon">
                P
            </div>

            <div>

                <h2>
                    PMS
                </h2>

                <span>
                    Project Management
                </span>

            </div>

        </div>


        <nav class="sidebar-nav">

            <p class="nav-title">
                MAIN
            </p>


            <a
                href="admin-dashboard.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▦
                </span>

                Dashboard

            </a>


            <a
                href="admin-users.php"
                class="nav-item active"
            >

                <span class="nav-icon">
                    ♙
                </span>

                Users

            </a>


            <a
                href="admin-projects.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▣
                </span>

                Projects

            </a>


            <p class="nav-title">
                MANAGEMENT
            </p>


            <a
                href="admin-reports.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▥
                </span>

                Reports

            </a>


            <a
                href="admin-activity.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ◷
                </span>

                Activity Logs

            </a>


            <p class="nav-title">
                ACCOUNT
            </p>


                        <a
                href="notifications.php"
                class="nav-item"
            >
                <span class="nav-icon">♧</span>
                Notifications
            </a>

            <a
                href="profile.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ◉
                </span>

                My Profile

            </a>

        </nav>


        <div class="sidebar-bottom">
            <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <span class="toggle-icon">🌙</span>
                <span>Dark Mode</span>
                <span class="toggle-track"></span>
            </button>
            <a
                href="logout.php"
                class="logout-item"
            >

                <span>
                    ↪
                </span>

                Logout

            </a>

        </div>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-left">

                <button
                    class="mobile-menu"
                    type="button"
                >
                    ☰
                </button>

            </div>


            <div class="topbar-right">

                <div class="admin-profile">

                    <?= render_avatar($_SESSION["profile_image"] ?? null, $_SESSION["full_name"], (int)($_SESSION["user_id"] ?? 0)) ?>


                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars(
                                $_SESSION["full_name"]
                            ) ?>

                        </strong>

                        <span>
                            Administrator
                        </span>

                    </div>

                </div>

            </div>

        </header>


        <!-- =====================================================
             CONTENT
        ====================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="page-header">

                <div>

                    <span class="page-label">
                        USER MANAGEMENT
                    </span>

                    <h1>
                        Add User
                    </h1>

                    <p>
                        Create a Project Manager or Team Member account.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 FORM
            ================================================== -->

            <div class="form-container">


                <div class="dashboard-card">


                    <?php if ($error !== ""): ?>

                        <div class="alert alert-error">

                            <?= htmlspecialchars($error) ?>

                        </div>

                    <?php endif; ?>


                    <?php if ($success !== ""): ?>

                        <div class="alert alert-success">

                            <?= htmlspecialchars($success) ?>

                        </div>

                    <?php endif; ?>


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
                                value="<?= htmlspecialchars(
                                    $full_name ?? ""
                                ) ?>"
                                placeholder="Enter full name"
                                required
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
                                value="<?= htmlspecialchars(
                                    $email ?? ""
                                ) ?>"
                                placeholder="Enter email address"
                                required
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
                                placeholder="Enter password"
                                minlength="6"
                                required
                            >

                            <small>
                                Password must contain at least 6 characters.
                            </small>

                        </div>


                        <!-- ROLE -->

                        <div class="form-group">

                            <label for="role">
                                Role
                            </label>

                            <select
                                id="role"
                                name="role"
                                required
                            >

                                <option
                                    value="project_manager"
                                >
                                    Project Manager
                                </option>

                                <option
                                    value="member"
                                >
                                    Team Member
                                </option>

                            </select>

                        </div>


                        <!-- BUTTONS -->

                        <div class="form-actions">

                            <a
                                href="admin-users.php"
                                class="secondary-button"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="primary-button"
                            >
                                Create User
                            </button>

                        </div>


                    </form>


                </div>


            </div>


        </section>

    </main>

</div>

<?php include "cookie_consent.php"; ?>
<script src="dark_mode.php"></script>
<script src="assets/js/responsive.js"></script>
</body>

</html>