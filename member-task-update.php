<?php

session_start();

require_once "config/database.php";
require_once "auth_check.php";
require_once "send_email_notification.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "member") {
    header("Location: dashboard.php");
    exit;
}

$member_id = (int) $_SESSION["user_id"];
$task_id = (int) ($_POST["task_id"] ?? 0);
$new_status = $_POST["status"] ?? "";

/* Members can ONLY move forward: to_do → in_progress → review */
$valid_statuses = ["to_do", "in_progress", "review"];

if ($task_id <= 0 || !in_array($new_status, $valid_statuses, true)) {
    header("Location: member-tasks.php?error=" . urlencode("Invalid request. Tasks must be approved by a manager or admin."));
    exit;
}

/* Verify the task is assigned to this member */
$stmt = mysqli_prepare($conn, "SELECT t.id, t.title, t.status AS old_status, t.project_id, p.name AS project_name FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ? AND t.assigned_to = ?");
mysqli_stmt_bind_param($stmt, "ii", $task_id, $member_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$task = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$task) {
    header("Location: member-tasks.php?error=" . urlencode("Task not found or access denied."));
    exit;
}

$old_status = $task["old_status"];

if ($old_status === $new_status) {
    header("Location: member-tasks.php?info=" . urlencode("Status unchanged."));
    exit;
}

/* Enforce forward-only: members can only go to_do→in_progress→review */
$member_valid = [
    "to_do"       => ["in_progress"],
    "in_progress" => ["review"],
    "review"      => [],  /* only manager/admin can approve */
];

if (!in_array($new_status, $member_valid[$old_status] ?? [], true)) {
    header("Location: member-tasks.php?error=" . urlencode("Cannot move task from '" . ucfirst(str_replace("_", " ", $old_status)) . "' to '" . ucfirst(str_replace("_", " ", $new_status)) . "'. Tasks must be approved by a manager or admin."));
    exit;
}

/* Update the task status */
$update = mysqli_prepare($conn, "UPDATE tasks SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($update, "si", $new_status, $task_id);
if (!mysqli_stmt_execute($update)) {
    header("Location: member-tasks.php?error=" . urlencode("Failed to update task status."));
    exit;
}
mysqli_stmt_close($update);

/* Activity Log */
if ($new_status === "review") {
    $action = "task_submitted_for_review";
    $log_description = "Task \"" . $task["title"] . "\" submitted for review and approval";
} else {
    $action = "task_updated";
    $log_description = "Task \"" . $task["title"] . "\" status changed from " . str_replace("_", " ", $old_status) . " to " . str_replace("_", " ", $new_status);
}

$activity = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, project_id, action, description) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($activity, "iiss", $member_id, $task["project_id"], $action, $log_description);
mysqli_stmt_execute($activity);
mysqli_stmt_close($activity);

/* Email notification */
send_notification_email($conn, $action, $log_description, $task["project_id"], $member_id);

/* In-app notification to project manager */
if ($new_status === "review") {
    $mgr_stmt = mysqli_prepare($conn, "SELECT p.manager_id, p.name AS project_name FROM projects p WHERE p.id = ? LIMIT 1");
    if ($mgr_stmt) {
        mysqli_stmt_bind_param($mgr_stmt, "i", $task["project_id"]);
        mysqli_stmt_execute($mgr_stmt);
        $mgr_result = mysqli_stmt_get_result($mgr_stmt);
        if ($mgr_row = mysqli_fetch_assoc($mgr_result)) {
            $member_name = $_SESSION["full_name"] ?? "Team Member";
            insert_user_notification(
                $conn,
                (int) $mgr_row["manager_id"],
                "Task Submitted for Review",
                $member_name . " submitted the task \"" . $task["title"] . "\" for review in project \"" . $mgr_row["project_name"] . "\"",
                "task_submitted_for_review"
            );
        }
        mysqli_stmt_close($mgr_stmt);
    }
}

header("Location: member-tasks.php?updated=1");
exit;
?>
