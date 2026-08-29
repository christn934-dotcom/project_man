<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| ADMIN PROTECTION
|--------------------------------------------------------------------------
*/

require_once "auth_check.php";
require_once "send_email_notification.php";

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

/*|--------------------------------------------------------------------------| ONLY POST|--------------------------------------------------------------------------|*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: users.php");
    exit;
}


$user_id = isset($_POST["id"])
    ? (int) $_POST["id"]
    : 0;


/*
|--------------------------------------------------------------------------
| VALIDATE USER ID
|--------------------------------------------------------------------------
*/

if ($user_id <= 0) {

    header("Location: users.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| NEVER MODIFY THE ADMIN ACCOUNT
|--------------------------------------------------------------------------
*/

if ($user_id === (int) $_SESSION["user_id"]) {

    header("Location: users.php?error=admin_protected");
    exit;

}


/*
|--------------------------------------------------------------------------
| GET CURRENT USER STATUS
|--------------------------------------------------------------------------
*/

$query = "
    SELECT id, role, status
    FROM users
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| USER NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$user) {

    header("Location: users.php?error=user_not_found");
    exit;

}


/*
|--------------------------------------------------------------------------
| DETERMINE NEW STATUS
|--------------------------------------------------------------------------
*/

$new_status =
    ($user["status"] === "active")
        ? "inactive"
        : "active";


/*
|--------------------------------------------------------------------------
| UPDATE STATUS
|--------------------------------------------------------------------------
*/

$query = "
    UPDATE users
    SET status = ?
    WHERE id = ?
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $new_status,
    $user_id
);

$success = mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

if ($success) {

    /* Activity log */
    $actor_id = (int) $_SESSION["user_id"];
    $new_status_label = $user["status"] === "active" ? "deactivated" : "activated";
    $log_desc = "User " . $user["role"] . " " . $new_status_label;
    $log_action = "user_updated";
    $lstmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    if ($lstmt) {
        mysqli_stmt_bind_param($lstmt, "iss", $actor_id, $log_action, $log_desc);
        mysqli_stmt_execute($lstmt);
        mysqli_stmt_close($lstmt);
    }

    header(
        "Location: users.php?status_changed=1"
    );

    exit;

}


header(
    "Location: users.php?error=status_change_failed"
);

exit;

?>