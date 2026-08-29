<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| DELETE REMEMBER ME TOKEN|--------------------------------------------------------------------------|*/

if (isset($_SESSION["user_id"])) {

    $user_id = (int) $_SESSION["user_id"];

    $del = mysqli_prepare($conn, "DELETE FROM remember_me WHERE user_id = ?");
    mysqli_stmt_bind_param($del, "i", $user_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

}


/*|--------------------------------------------------------------------------| CLEAR REMEMBER ME COOKIE|--------------------------------------------------------------------------|*/

if (isset($_COOKIE["remember_me"])) {

    setcookie(
        "remember_me",
        "",
        time() - 3600,
        "/",
        "",
        false,
        true
    );

    unset($_COOKIE["remember_me"]);

}


/*|--------------------------------------------------------------------------| DESTROY SESSION|--------------------------------------------------------------------------|*/

$_SESSION = [];

session_destroy();

header("Location: login.php");
exit;

?>