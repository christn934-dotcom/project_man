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

$query = "SELECT t.id, t.title, t.assigned_to, t.project_id, p.manager_id, p.name AS project_name
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
$task_title = $task["title"] ?? "Unknown";
$task_project_id = (int) ($task["project_id"] ?? 0);
$log_desc = "Deleted task: " . $task_title;
$log_action = "task_deleted";
$lstmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, project_id, action, description) VALUES (?, ?, ?, ?)");
if ($lstmt) {
    mysqli_stmt_bind_param($lstmt, "iiss", $user_id, $task_project_id, $log_action, $log_desc);
    mysqli_stmt_execute($lstmt);
    mysqli_stmt_close($lstmt);
}

/* EMAIL: Notify assigned member + project manager about deletion */
$actor_name = $_SESSION["full_name"] ?? "User";
$task_emails = [];

/* Assigned member */
$assigned_to = (int) ($task["assigned_to"] ?? 0);
if ($assigned_to > 0 && $assigned_to !== $user_id) {
    $mstmt = mysqli_prepare($conn, "SELECT email, full_name FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($mstmt, "i", $assigned_to);
    mysqli_stmt_execute($mstmt);
    $mrow = mysqli_fetch_assoc(mysqli_stmt_get_result($mstmt));
    mysqli_stmt_close($mstmt);
    if ($mrow) {
        $task_emails[] = $mrow["email"];
    }
}

/* Project manager */
$manager_id = (int) ($task["manager_id"] ?? 0);
if ($manager_id > 0 && $manager_id !== $user_id) {
    $mstmt2 = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($mstmt2, "i", $manager_id);
    mysqli_stmt_execute($mstmt2);
    $mrow2 = mysqli_fetch_assoc(mysqli_stmt_get_result($mstmt2));
    mysqli_stmt_close($mstmt2);
    if ($mrow2 && !in_array($mrow2["email"], $task_emails, true)) {
        $task_emails[] = $mrow2["email"];
    }
}

if (!empty($task_emails)) {
    $label = "Task Deleted";
    $project_name = $task["project_name"] ?? "Unknown Project";
    $subject = "[$label] $project_name — PROMASY";
    $html = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fb;padding:40px 20px;"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);"><tr><td style="background:linear-gradient(135deg,#4f46e5,#6366f1);padding:30px 40px;text-align:center;"><h1 style="margin:0;color:#fff;font-size:24px;letter-spacing:2px;">PROMASY</h1></td></tr><tr><td style="padding:30px 40px 0;text-align:center;"><div style="display:inline-block;background:#fef2f2;color:#dc2626;padding:6px 16px;border-radius:20px;font-size:12px;font-weight:600;">' . htmlspecialchars($label) . '</div></td></tr><tr><td style="padding:20px 40px;"><h2 style="margin:0 0 12px;color:#1f2937;font-size:18px;">Hello!</h2><p style="margin:0 0 16px;color:#4b5563;font-size:14px;line-height:1.6;"><strong>' . htmlspecialchars($actor_name) . '</strong> has deleted the task <strong>"' . htmlspecialchars($task_title) . '"</strong> from project <strong>"' . htmlspecialchars($project_name) . '"</strong>.</p></td></tr><tr><td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #e5e7eb;"><p style="margin:0;color:#9ca3af;font-size:11px;text-align:center;">Automated notification from PROMASY</p></td></tr></table></td></tr></table></body></html>';
    $text = "$label\n" . str_repeat("=", 40) . "\n\n$actor_name has deleted the task \"$task_title\" from project \"$project_name\".\n\n---\nAutomated notification from PROMASY.";
    foreach ($task_emails as $email) {
        send_single_email($email, $subject, $html, $text);
    }
}

$redirect = ($role === "admin") ? "tasks.php" : "manager-tasks.php";

if ($success) {
    header("Location: $redirect?deleted=1");
} else {
    header("Location: $redirect?error=delete_failed");
}

exit;

?>
