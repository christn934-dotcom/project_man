<?php

session_start();


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| ROLE-BASED REDIRECTION
|--------------------------------------------------------------------------
|
| This file is a pure dispatcher. Every page in the app sends a
| logged-in user here whenever they hit a page their role isn't
| allowed to see, so we forward them on to the dashboard that
| matches their actual role.
|
*/

$role = $_SESSION["role"] ?? "";

if ($role === "admin") {

    header("Location: admin-dashboard.php");
    exit;

}

elseif ($role === "project_manager") {

    header("Location: manager-dashboard.php");
    exit;

}

elseif ($role === "member") {

    header("Location: member-dashboard.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| UNKNOWN / INVALID ROLE
|--------------------------------------------------------------------------
|
| If the session doesn't map to a known role, the session is bad.
| Destroy it and send the user back to login rather than looping.
|
*/

$_SESSION = [];

session_destroy();

header("Location: login.php");
exit;

?>