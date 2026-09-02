<?php

/*
|--------------------------------------------------------------------------
| DATABASE CONFIGURATION
|--------------------------------------------------------------------------
| 000WebHost: Credentials come from your hosting control panel.
| Local WAMP: Uses default localhost settings below.
|--------------------------------------------------------------------------
*/

if (file_exists(__DIR__ . "/env.php")) {
    require_once __DIR__ . "/env.php";
} else {
    /* Local WAMP defaults */
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "project_man";
}

$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    /* If connection fails, redirect to install page */
    if (!headers_sent() && basename($_SERVER["SCRIPT_NAME"] ?? "") !== "install.php") {
        header("Location: install.php");
        exit;
    }
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

?>