<?php
/**
 * Notification Settings API
 * 
 * GET  — returns current settings as JSON
 * POST — saves settings (admin only)
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {

    $result = mysqli_query($conn, "SELECT setting_key, setting_value FROM notification_settings");
    $settings = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row["setting_key"]] = $row["setting_value"];
        }
    }

    echo json_encode($settings);
    exit;

} elseif ($method === "POST") {

    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON"]);
        exit;
    }

    $allowed_keys = [
        "email_notifications_enabled",
        "formspree_form_id",
        "email_events",
        "deadline_alerts",
        "dashboard_notifications",
    ];

    $stmt = mysqli_prepare($conn, "INSERT INTO notification_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY VALUES setting_value = VALUES(setting_value)");

    // MySQL < 8.0.20 doesn't support ON DUPLICATE KEY VALUES — use alternative
    foreach ($input as $key => $value) {
        if (!in_array($key, $allowed_keys, true)) {
            continue;
        }
        $val = is_array($value) ? json_encode($value) : (string) $value;
        $stmt = mysqli_prepare($conn, "INSERT INTO notification_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $key, $val, $val);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    echo json_encode(["success" => true]);
    exit;

} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}
