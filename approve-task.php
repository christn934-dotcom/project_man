<?php

session_start();

require_once "config/database.php";
require_once "config/url.php";
require_once "auth_check.php";
require_once "send_email_notification.php";

/* Check login */
if (!isset($_SESSION["role"])) {
    header("Location: dashboard.php");
    exit;
}

$role = $_SESSION["role"];
$user_id = (int) $_SESSION["user_id"];

/* Only admin and manager can approve tasks */
if ($role !== "admin" && $role !== "project_manager") {
    header("Location: dashboard.php");
    exit;
}

/* Validate POST */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

$task_id = (int) ($_POST["task_id"] ?? 0);
$action_type = $_POST["approve_action"] ?? ""; /* "approve" or "reject" */

if ($task_id <= 0 || !in_array($action_type, ["approve", "reject"], true)) {
    $redirect = ($role === "admin") ? "tasks.php" : "manager-tasks.php";
    header("Location: $redirect?error=" . urlencode("Invalid request."));
    exit;
}

/* Fetch task */
$stmt = mysqli_prepare($conn, "SELECT t.id, t.title, t.status, t.project_id, t.assigned_to, p.manager_id, p.name AS project_name FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ?");
mysqli_stmt_bind_param($stmt, "i", $task_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$task = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$task) {
    $redirect = ($role === "admin") ? "tasks.php" : "manager-tasks.php";
    header("Location: $redirect?error=" . urlencode("Task not found."));
    exit;
}

/* Authorization: admin can approve any task; manager can only approve tasks on their projects */
if ($role === "project_manager" && (int) $task["manager_id"] !== $user_id) {
    header("Location: manager-tasks.php?error=" . urlencode("Access denied."));
    exit;
}

/* Task must be in review status */
if ($task["status"] !== "review") {
    $redirect = ($role === "admin") ? "tasks.php" : "manager-tasks.php";
    header("Location: $redirect?error=" . urlencode("Task is not pending review."));
    exit;
}

/* Set new status */
$new_status = ($action_type === "approve") ? "completed" : "in_progress";
$status_label = ($action_type === "approve") ? "approved" : "sent back for revisions";

/* Update task */
$update = mysqli_prepare($conn, "UPDATE tasks SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($update, "si", $new_status, $task_id);
if (!mysqli_stmt_execute($update)) {
    $redirect = ($role === "admin") ? "tasks.php" : "manager-tasks.php";
    header("Location: $redirect?error=" . urlencode("Failed to update task."));
    exit;
}
mysqli_stmt_close($update);

/* Activity Log */
$actor_name = $_SESSION["full_name"] ?? "User";
$action = ($action_type === "approve") ? "task_approved" : "task_rejected";
$log_description = "Task \"" . $task["title"] . "\" " . $status_label . " by " . $actor_name;

$activity = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, project_id, action, description) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($activity, "iiss", $user_id, $task["project_id"], $action, $log_description);
mysqli_stmt_execute($activity);
mysqli_stmt_close($activity);

/* Email notification to the assigned member + project manager */
send_notification_email($conn, $action, $log_description, $task["project_id"], $user_id, $task_id);

/* In-app notification to the assigned member */
if ((int) $task["assigned_to"] > 0 && (int) $task["assigned_to"] !== $user_id) {
    $notif_title = ($action_type === "approve") ? "Task Approved" : "Task Sent Back";
    $notif_msg = $actor_name . " " . $status_label . " your task \"" . $task["title"] . "\" in project \"" . $task["project_name"] . "\"";
    insert_user_notification($conn, (int) $task["assigned_to"], $notif_title, $notif_msg, $action);
}

$redirect = ($role === "admin") ? "tasks.php" : "manager-tasks.php";
$msg = ($action_type === "approve") ? "Task approved and marked as completed." : "Task sent back for revisions.";
header("Location: $redirect?updated=1&msg=" . urlencode($msg));
exit;
?>
