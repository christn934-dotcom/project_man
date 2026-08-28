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


/*|--------------------------------------------------------------------------| Get Project ID|--------------------------------------------------------------------------|*/

$project_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($project_id <= 0) {
    header("Location: projects.php?error=invalid_project");
    exit;
}


/*|--------------------------------------------------------------------------| Check Project Exists|--------------------------------------------------------------------------|*/

$query = "SELECT id, name FROM projects WHERE id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $project_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$project = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$project) {
    header("Location: projects.php?error=not_found");
    exit;
}


/*|--------------------------------------------------------------------------| Delete Project|--------------------------------------------------------------------------|*/

mysqli_begin_transaction($conn);

try {

    /* Delete project members */
    $del = mysqli_prepare($conn, "DELETE FROM project_members WHERE project_id = ?");
    mysqli_stmt_bind_param($del, "i", $project_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    /* Delete tasks */
    $del = mysqli_prepare($conn, "DELETE FROM tasks WHERE project_id = ?");
    mysqli_stmt_bind_param($del, "i", $project_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    /* Delete activity logs */
    $del = mysqli_prepare($conn, "DELETE FROM activity_logs WHERE project_id = ?");
    mysqli_stmt_bind_param($del, "i", $project_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    /* Delete the project */
    $del = mysqli_prepare($conn, "DELETE FROM projects WHERE id = ?");
    mysqli_stmt_bind_param($del, "i", $project_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    mysqli_commit($conn);

    header("Location: projects.php?deleted=1");
    exit;


} catch (Exception $e) {

    mysqli_rollback($conn);
    die("Delete failed: " . $e->getMessage());

}

?>
