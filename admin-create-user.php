<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

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

                    <div class="profile-avatar">

                        <?= htmlspecialchars(
                            strtoupper(
                                substr(
                                    $_SESSION["full_name"],
                                    0,
                                    2
                                )
                            )
                        ) ?>

                    </div>


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

</body>

</html>