<?php

session_start();


/*|--------------------------------------------------------------------------| CHECK LOGIN|--------------------------------------------------------------------------|*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


$role = $_SESSION["role"] ?? "";


if ($role === "admin") {
    header("Location: admin-reports.php");
    exit;
}

elseif ($role === "project_manager") {
    header("Location: manager-reports.php");
    exit;
}

else {
    header("Location: dashboard.php");
    exit;
}

?>
