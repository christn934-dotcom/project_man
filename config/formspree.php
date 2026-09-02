<?php

/**
 * Email Notification Configuration
 * 
 * Loads settings from the notification_settings DB table.
 * Falls back to hardcoded defaults if the table doesn't exist yet.
 * 
 * PRIMARY METHOD: PHP mail() — sends to each user's email
 * FALLBACK: Formspree — sends to form owner's email
 */

// Hardcoded defaults (used if DB table not yet created)
$defaults = [
    'email_notifications_enabled' => '1',
    'formspree_form_id'           => 'mnpqqobe',
    'email_events'                => 'task_created,task_updated,task_deleted,task_submitted_for_review,task_approved,task_rejected,project_created,project_updated,project_deleted,project_submitted_for_approval,project_approved,project_rejected,user_created,user_deleted,user_updated',
];

// Try loading from DB
$db_settings = [];

if (isset($conn) && $conn instanceof mysqli) {
    $result = @mysqli_query($conn, "SELECT setting_key, setting_value FROM notification_settings");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $db_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// Merge: DB overrides defaults
$merged = array_merge($defaults, $db_settings);

// Set globals
$EMAIL_NOTIFICATIONS_ENABLED = ($merged['email_notifications_enabled'] ?? '1') === '1';

$FORMSPREE_FORM_ID  = $merged['formspree_form_id'] ?? 'mnpqqobe';
$FORMSPREE_ENDPOINT = "https://formspree.io/f/" . $FORMSPREE_FORM_ID;

$EMAIL_NOTIFICATION_ACTIONS = array_filter(array_map('trim', explode(',', $merged['email_events'] ?? '')));

?>
