<?php
session_start();
require_once "../config/database.php";

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id <= 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = mysqli_prepare($conn, "SELECT theme FROM user_settings WHERE user_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        echo json_encode(['theme' => $row ? ($row['theme'] ?? 'light') : 'light']);
    } else {
        echo json_encode(['theme' => 'light']);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $theme = ($input['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';

    // Upsert: insert or update
    $stmt = mysqli_prepare($conn, "INSERT INTO user_settings (user_id, theme) VALUES (?, ?) ON DUPLICATE KEY UPDATE theme = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $theme, $theme);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['ok' => $ok, 'theme' => $theme]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'DB error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
