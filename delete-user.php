<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| ADMIN PROTECTION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

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

$user_id = isset($_GET["id"])
    ? (int) $_GET["id"]
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

$query = "
    DELETE FROM users
    WHERE id = ?
";

$stmt = mysqli_prepare(
    $conn,
    $query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

$success =
    mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

if ($success) {

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