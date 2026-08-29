<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| Admin Protection|--------------------------------------------------------------------------|*/

require_once "auth_check.php";
require_once "send_email_notification.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit;
}


/*|--------------------------------------------------------------------------| Only POST|--------------------------------------------------------------------------|*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: users.php");
    exit;
}


/*|--------------------------------------------------------------------------| Get Form Data|--------------------------------------------------------------------------|*/

$full_name = trim($_POST["full_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$role = $_POST["role"] ?? "member";


/*|--------------------------------------------------------------------------| Validation|--------------------------------------------------------------------------|*/

$error = "";

if ($full_name === "") {
    $error = "Full name is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Please enter a valid email address.";
} elseif (strlen($password) < 8) {
    $error = "Password must contain at least 8 characters.";
} elseif ($role !== "project_manager" && $role !== "member") {
    $error = "Invalid role.";
}


/*|--------------------------------------------------------------------------| Check Email|--------------------------------------------------------------------------|*/

if ($error === "") {

    $check = mysqli_prepare(
        $conn,
        "SELECT id FROM users WHERE email = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    $result = mysqli_stmt_get_result($check);

    if (mysqli_num_rows($result) > 0) {
        $error = "A user with this email already exists.";
    }

    mysqli_stmt_close($check);

}


/*|--------------------------------------------------------------------------| Create User|--------------------------------------------------------------------------|*/

if ($error === "") {

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (full_name, email, password, role, status)
         VALUES (?, ?, ?, ?, 'active')"
    );

    mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $hashed_password, $role);

    if (mysqli_stmt_execute($stmt)) {
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

    header("Location: users.php?created=1");
        exit;
    } else {
        $error = "Unable to create user: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);

}


/*|--------------------------------------------------------------------------| REDIRECT ON ERROR|--------------------------------------------------------------------------|*/

if ($error !== "") {
    header("Location: users.php?error=" . urlencode($error));
    exit;
}

?>
