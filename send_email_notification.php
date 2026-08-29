<?php

/**
 * Email Notification Helper
 * 
 * PRIMARY: PHP mail() — sends directly to each user's email address.
 * FALLBACK: Formspree API — sends via your Formspree form.
 * 
 * Usage:
 *   require_once "send_email_notification.php";
 *   send_notification_email($conn, $action, $description, $project_id, $exclude_user_id);
 */

require_once __DIR__ . "/config/formspree.php";


/* ============================================================
   RECIPIENT LOOKUP FUNCTIONS
   ============================================================ */

/**
 * Get the recipient email addresses for a given project
 * (manager + all assigned members), optionally excluding one user.
 */
function get_project_recipient_emails(
    $conn,
    $project_id,
    $exclude_user_id = 0
) {

    $emails = [];

    /* Manager email */
    $query = "
        SELECT u.email
        FROM projects p
        INNER JOIN users u ON p.manager_id = u.id
        WHERE p.id = ?
        AND p.manager_id != ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $project_id, $exclude_user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $emails[] = $row["email"];
        }

        mysqli_stmt_close($stmt);
    }

    /* Member emails */
    $query2 = "
        SELECT u.email
        FROM project_members pm
        INNER JOIN users u ON pm.user_id = u.id
        WHERE pm.project_id = ?
        AND pm.user_id != ?
    ";

    $stmt2 = mysqli_prepare($conn, $query2);

    if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, "ii", $project_id, $exclude_user_id);
        mysqli_stmt_execute($stmt2);
        $result2 = mysqli_stmt_get_result($stmt2);

        while ($row = mysqli_fetch_assoc($result2)) {
            if (!in_array($row["email"], $emails, true)) {
                $emails[] = $row["email"];
            }
        }

        mysqli_stmt_close($stmt2);
    }

    return $emails;
}


/**
 * Get the recipient email for a specific task assignment.
 * If the task is assigned to a member, notify them.
 * Also notify the project manager.
 */
function get_task_recipient_emails(
    $conn,
    $task_id,
    $exclude_user_id = 0
) {

    $emails = [];

    $query = "
        SELECT t.assigned_to, t.project_id, p.manager_id
        FROM tasks t
        INNER JOIN projects p ON t.project_id = p.id
        WHERE t.id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $task_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {

            /* Assigned member */
            if (
                $row["assigned_to"] > 0 &&
                (int) $row["assigned_to"] !== $exclude_user_id
            ) {
                $member_query = "SELECT email FROM users WHERE id = ? LIMIT 1";
                $mstmt = mysqli_prepare($conn, $member_query);
                if ($mstmt) {
                    mysqli_stmt_bind_param($mstmt, "i", $row["assigned_to"]);
                    mysqli_stmt_execute($mstmt);
                    $mresult = mysqli_stmt_get_result($mstmt);
                    if ($mrow = mysqli_fetch_assoc($mresult)) {
                        $emails[] = $mrow["email"];
                    }
                    mysqli_stmt_close($mstmt);
                }
            }

            /* Project manager */
            if (
                (int) $row["manager_id"] !== $exclude_user_id
            ) {
                $mgr_query = "SELECT email FROM users WHERE id = ? LIMIT 1";
                $mstmt2 = mysqli_prepare($conn, $mgr_query);
                if ($mstmt2) {
                    mysqli_stmt_bind_param($mstmt2, "i", $row["manager_id"]);
                    mysqli_stmt_execute($mstmt2);
                    $mresult2 = mysqli_stmt_get_result($mstmt2);
                    if ($mrow2 = mysqli_fetch_assoc($mresult2)) {
                        if (!in_array($mrow2["email"], $emails, true)) {
                            $emails[] = $mrow2["email"];
                        }
                    }
                    mysqli_stmt_close($mstmt2);
                }
            }
        }

        mysqli_stmt_close($stmt);
    }

    return $emails;
}


/**
 * Get a human-readable label for an action.
 */
function action_label($action) {
    $labels = [
        "task_created"    => "New Task Created",
        "task_updated"    => "Task Updated",
        "task_deleted"    => "Task Deleted",
        "project_created" => "New Project Created",
        "project_updated" => "Project Updated",
        "project_deleted" => "Project Deleted",
        "user_created"    => "New User Created",
        "user_updated"    => "User Updated",
        "user_deleted"    => "User Deleted",
    ];
    return $labels[$action] ?? "Notification";
}


/* ============================================================
   SENDING FUNCTIONS
   ============================================================ */

/**
 * Build a beautiful HTML email body for notifications.
 */
function build_notification_html($actor_name, $description, $project_name, $label) {

    $login_url = "http://localhost:8080/dashboard.php";

    return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fb;padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);">

<!-- Header -->
<tr><td style="background:linear-gradient(135deg,#4f46e5,#6366f1);padding:30px 40px;text-align:center;">
    <h1 style="margin:0;color:#ffffff;font-size:24px;letter-spacing:2px;">PROMASY</h1>
    <p style="margin:8px 0 0;color:rgba(255,255,255,0.8);font-size:13px;">Project Management System</p>
</td></tr>

<!-- Badge -->
<tr><td style="padding:30px 40px 0;text-align:center;">
    <div style="display:inline-block;background:#eef2ff;color:#4f46e5;padding:6px 16px;border-radius:20px;font-size:12px;font-weight:600;">' . htmlspecialchars($label) . '</div>
</td></tr>

<!-- Content -->
<tr><td style="padding:20px 40px;">
    <h2 style="margin:0 0 12px;color:#1f2937;font-size:18px;">Hello!</h2>
    <p style="margin:0 0 16px;color:#4b5563;font-size:14px;line-height:1.6;">
        <strong>' . htmlspecialchars($actor_name) . '</strong> ' . htmlspecialchars($description) . '
    </p>
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;">
        <tr>
            <td style="padding:12px 16px;font-size:13px;color:#6b7280;border-bottom:1px solid #e5e7eb;"><strong>Project</strong></td>
            <td style="padding:12px 16px;font-size:13px;color:#1f2937;border-bottom:1px solid #e5e7eb;">' . htmlspecialchars($project_name) . '</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;font-size:13px;color:#6b7280;"><strong>Action</strong></td>
            <td style="padding:12px 16px;font-size:13px;color:#1f2937;">' . htmlspecialchars($label) . '</td>
        </tr>
    </table>
</td></tr>

<!-- Button -->
<tr><td style="padding:0 40px 30px;text-align:center;">
    <a href="' . $login_url . '" style="display:inline-block;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:14px;font-weight:600;">View in PROMASY →</a>
</td></tr>

<!-- Footer -->
<tr><td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #e5e7eb;">
    <p style="margin:0;color:#9ca3af;font-size:11px;text-align:center;line-height:1.5;">
        This is an automated notification from PROMASY.<br>
        You are receiving this because you are a member of the project: ' . htmlspecialchars($project_name) . '
    </p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>';
}


/**
 * Build a plain text version of the email.
 */
function build_notification_text($actor_name, $description, $project_name, $label) {

    $text  = "PROMASY Notification\n";
    $text .= str_repeat("=", 40) . "\n\n";
    $text .= "Action: $label\n";
    $text .= "Project: $project_name\n\n";
    $text .= "$actor_name $description\n\n";
    $text .= "Log in to view details:\n";
    $text .= "http://localhost:8080/dashboard.php\n\n";
    $text .= "---\n";
    $text .= "This is an automated notification from PROMASY.\n";

    return $text;
}


/**
 * Send a single email via PHP mail().
 * Returns true on success, false on failure.
 */
function send_php_mail($to, $subject, $html_body, $text_body) {

    global $EMAIL_NOTIFICATIONS_ENABLED;

    if (!$EMAIL_NOTIFICATIONS_ENABLED) {
        return false;
    }

    $boundary = md5(uniqid(time()));

    $headers  = "From: PROMASY <noreply@promasy.local>\r\n";
    $headers .= "Reply-To: PROMASY <noreply@promasy.local>\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";

    $message  = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $text_body . "\r\n\r\n";
    $message .= "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $html_body . "\r\n\r\n";
    $message .= "--$boundary--\r\n";

    $result = @mail($to, $subject, $message, $headers);

    if (!$result) {
        error_log("PROMASY mail() failed for: $to");
    }

    return $result;
}


/**
 * Send a single email via Formspree API.
 * Returns true on success, false on failure.
 */
function send_formspree_email($to, $subject, $body) {

    global $FORMSPREE_ENDPOINT, $EMAIL_NOTIFICATIONS_ENABLED;

    if (!$EMAIL_NOTIFICATIONS_ENABLED) {
        return false;
    }

    if (
        empty($FORMSPREE_ENDPOINT) ||
        $FORMSPREE_ENDPOINT === "https://formspree.io/f/YOUR_FORMSPREE_FORM_ID"
    ) {
        error_log("Formspree: form ID not configured. Skipping email to $to");
        return false;
    }

    $payload = json_encode([
        "_subject" => $subject,
        "_replyto" => $to,
        "to"       => "admin@promasy.local",
        "from"     => "PROMASY <noreply@promasy.com>",
        "message"  => "Notification for: $to\n\n$body",
    ]);

    $ch = curl_init($FORMSPREE_ENDPOINT);

    if (!$ch) {
        error_log("Formspree: curl_init failed");
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Accept: application/json",
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);

    curl_close($ch);

    if ($error) {
        error_log("Formspree curl error: $error");
        return false;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }

    error_log("Formspree HTTP $httpCode for $to: $response");
    return false;
}


/**
 * Send a single notification email to one recipient.
 * Tries PHP mail() first, falls back to Formspree.
 * 
 * @param string $to       Recipient email address
 * @param string $subject  Email subject
 * @param string $html     HTML body
 * @param string $text     Plain text body
 * @return bool
 */
function send_single_email($to, $subject, $html, $text) {

    // Try PHP mail() first (sends directly to user's email)
    $sent = send_php_mail($to, $subject, $html, $text);

    // If mail() fails, try Formspree as fallback
    if (!$sent) {
        $sent = send_formspree_email($to, $subject, $text);
    }

    return $sent;
}


/* ============================================================
   MAIN NOTIFICATION FUNCTION
   ============================================================ */

/**
 * Send notification emails for a project/task action.
 * Each recipient gets their own email at their registered address.
 * 
 * @param mysqli  $conn             Database connection
 * @param string  $action           Action identifier (task_created, etc.)
 * @param string  $description      Human-readable description
 * @param int     $project_id       Related project ID
 * @param int     $exclude_user_id  User who triggered the action (don't email them)
 * @param int     $task_id          Optional: related task ID for task-specific notifications
 */
function send_notification_email(
    $conn,
    $action,
    $description,
    $project_id,
    $exclude_user_id = 0,
    $task_id = 0
) {

    global $EMAIL_NOTIFICATION_ACTIONS;

    /* Check if this action should trigger an email */
    if (!in_array($action, $EMAIL_NOTIFICATION_ACTIONS, true)) {
        return;
    }

    /* Get project name */
    $project_name = "";
    $pstmt = mysqli_prepare(
        $conn,
        "SELECT name FROM projects WHERE id = ? LIMIT 1"
    );
    if ($pstmt) {
        mysqli_stmt_bind_param($pstmt, "i", $project_id);
        mysqli_stmt_execute($pstmt);
        $presult = mysqli_stmt_get_result($pstmt);
        if ($prow = mysqli_fetch_assoc($presult)) {
            $project_name = $prow["name"];
        }
        mysqli_stmt_close($pstmt);
    }

    /* Determine recipients */
    if ($task_id > 0) {
        $recipients = get_task_recipient_emails($conn, $task_id, $exclude_user_id);
    } else {
        $recipients = get_project_recipient_emails($conn, $project_id, $exclude_user_id);
    }

    if (empty($recipients)) {
        return;
    }

    /* Get the actor's name */
    $actor_name = "Someone";
    $astmt = mysqli_prepare(
        $conn,
        "SELECT full_name FROM users WHERE id = ? LIMIT 1"
    );
    if ($astmt) {
        mysqli_stmt_bind_param($astmt, "i", $exclude_user_id);
        mysqli_stmt_execute($astmt);
        $aresult = mysqli_stmt_get_result($astmt);
        if ($arow = mysqli_fetch_assoc($aresult)) {
            $actor_name = $arow["full_name"];
        }
        mysqli_stmt_close($astmt);
    }

    /* Build email */
    $label   = action_label($action);
    $subject = "[$label] $project_name — PROMASY";
    $html    = build_notification_html($actor_name, $description, $project_name, $label);
    $text    = build_notification_text($actor_name, $description, $project_name, $label);

    /* Send to each recipient at their own email address */
    foreach ($recipients as $email) {
        if (!empty($email)) {
            send_single_email($email, $subject, $html, $text);
        }
    }
}


/**
 * Send a direct email notification to a specific user.
 * Useful for user-specific alerts (account created, password reset, etc.)
 */
function send_user_notification_email(
    $conn,
    $user_id,
    $subject,
    $body
) {

    $query = "SELECT email, full_name FROM users WHERE id = ? LIMIT 1";
    $stmt  = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $html = '<!DOCTYPE html>
<html><body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fb;padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);">
<tr><td style="background:linear-gradient(135deg,#4f46e5,#6366f1);padding:30px 40px;text-align:center;">
    <h1 style="margin:0;color:#fff;font-size:24px;letter-spacing:2px;">PROMASY</h1>
</td></tr>
<tr><td style="padding:30px 40px;">
    <p style="color:#4b5563;font-size:14px;line-height:1.6;">' . nl2br(htmlspecialchars($body)) . '</p>
</td></tr>
<tr><td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #e5e7eb;">
    <p style="margin:0;color:#9ca3af;font-size:11px;text-align:center;">Automated notification from PROMASY</p>
</td></tr>
</table></td></tr></table>
</body></html>';

            send_single_email($row["email"], $subject, $html, $body);
        }

        mysqli_stmt_close($stmt);
    }
}


?>
