<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| CHECK LOGIN|--------------------------------------------------------------------------|*/

require_once "auth_check.php";
require_once "send_email_notification.php";


/*|--------------------------------------------------------------------------| CHECK ROLE|--------------------------------------------------------------------------|*/

$role = $_SESSION["role"] ?? "";

if ($role !== "admin" && $role !== "project_manager") {
    header("Location: dashboard.php");
    exit;
}


$user_id = (int) $_SESSION["user_id"];


/*|--------------------------------------------------------------------------| GET TASK ID|--------------------------------------------------------------------------|*/

/*|--------------------------------------------------------------------------| ONLY POST|--------------------------------------------------------------------------|*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: tasks.php");
    exit;
}


$task_id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;

if ($task_id <= 0) {
    header("Location: tasks.php");
    exit;
}


/*|--------------------------------------------------------------------------| GET TASK|--------------------------------------------------------------------------|*/

$query = "SELECT t.id, t.title, t.project_id, p.manager_id
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    WHERE t.id = ?
    LIMIT 1";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $task_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$task = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$task) {
    header("Location: tasks.php");
    exit;
}


/*|--------------------------------------------------------------------------| AUTHORIZATION|--------------------------------------------------------------------------|*/

if ($role !== "admin" && (int) $task["manager_id"] !== $user_id) {
    header("Location: tasks.php");
    exit;
}


/*|--------------------------------------------------------------------------| DELETE TASK|--------------------------------------------------------------------------|*/

$del = mysqli_prepare($conn, "DELETE FROM tasks WHERE id = ?");
mysqli_stmt_bind_param($del, "i", $task_id);
$success = mysqli_stmt_execute($del);
mysqli_stmt_close($del);


/* Activity log */
$log_desc = "Deleted task: " . ($task["title"] ?? "Unknown");
$log_action = "task_deleted";
$task_project_id = (int) ($task["project_id"] ?? 0);
$lstmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, project_id, action, description) VALUES (?, ?, ?, ?)");
if ($lstmt) {
    mysqli_stmt_bind_param($lstmt, "iiss", $user_id, $task_project_id, $log_action, $log_desc);
    mysqli_stmt_execute($lstmt);
    mysqli_stmt_close($lstmt);
}

$redirect = ($role === "admin") ? "tasks.php" : "manager-tasks.php";

if ($success) {
    header("Location: $redirect?deleted=1");
} else {
    header("Location: $redirect?error=delete_failed");
}

exit;

?>
