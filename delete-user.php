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


if ($user_id <= 0) {
    header("Location: users.php?error=invalid_user");
    exit;
}


/*
|--------------------------------------------------------------------------
| NEVER DELETE THE CURRENT ADMIN
|--------------------------------------------------------------------------
*/

if ($user_id === (int) $_SESSION["user_id"]) {

    header("Location: users.php?error=admin_protected");
    exit;

}


/*
|--------------------------------------------------------------------------
| CHECK USER EXISTS
|--------------------------------------------------------------------------
*/

$query = "
    SELECT id, full_name, role
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


if (!$user) {

    header(
        "Location: users.php?error=user_not_found"
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| EXTRA ADMIN PROTECTION
|--------------------------------------------------------------------------
|
| Even if someone manually changes the URL, an Admin
| account cannot be deleted.
|--------------------------------------------------------------------------
*/

if ($user["role"] === "admin") {

    header(
        "Location: users.php?error=admin_protected"
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| DELETE USER
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction($conn);

try {

    /* Remove from project_members */
    $del = mysqli_prepare($conn, "DELETE FROM project_members WHERE user_id = ?");
    mysqli_stmt_bind_param($del, "i", $user_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    /* Unassign tasks */
    $upd = mysqli_prepare($conn, "UPDATE tasks SET assigned_to = NULL WHERE assigned_to = ?");
    mysqli_stmt_bind_param($upd, "i", $user_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    /* Delete the user */
    $del = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($del, "i", $user_id);
    $success = mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    mysqli_commit($conn);

} catch (Exception $e) {
    mysqli_rollback($conn);
    $success = false;
}


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

if ($success) {

    /* Activity log */
    $actor_id = (int) $_SESSION["user_id"];
    $log_desc = "Deleted user: " . $user["full_name"];
    $log_action = "user_deleted";
    $lstmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    if ($lstmt) {
        mysqli_stmt_bind_param($lstmt, "iss", $actor_id, $log_action, $log_desc);
        mysqli_stmt_execute($lstmt);
        mysqli_stmt_close($lstmt);
    }

    header(
        "Location: users.php?deleted=1"
    );

    exit;

}


header(
    "Location: users.php?error=delete_failed"
);

exit;

?>