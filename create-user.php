<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| Admin Protection|--------------------------------------------------------------------------|*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

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

if ($full_name === "") {
    die("Full name is required.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Please enter a valid email address.");
}

if (strlen($password) < 8) {
    die("Password must contain at least 8 characters.");
}

if ($role !== "project_manager" && $role !== "member") {
    die("Invalid role.");
}


/*|--------------------------------------------------------------------------| Check Email|--------------------------------------------------------------------------|*/

$check = mysqli_prepare(
    $conn,
    "SELECT id FROM users WHERE email = ? LIMIT 1"
);

mysqli_stmt_bind_param($check, "s", $email);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);

if (mysqli_num_rows($result) > 0) {
    mysqli_stmt_close($check);
    die("A user with this email already exists.");
}

mysqli_stmt_close($check);


/*|--------------------------------------------------------------------------| Create User|--------------------------------------------------------------------------|*/

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO users (full_name, email, password, role, status)
     VALUES (?, ?, ?, ?, 'active')"
);

mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $hashed_password, $role);

if (mysqli_stmt_execute($stmt)) {
    header("Location: users.php?created=1");
    exit;
} else {
    die("Unable to create user: " . mysqli_error($conn));
}

?>
