<?php
/**
 * Serve a user's profile picture.
 * Usage: serve_avatar.php?id=1
 */

session_start();

// Only authenticated users can view profile pictures
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

require_once "config/database.php";

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    http_response_code(404);
    exit;
}

// Fetch the profile image path
$stmt = mysqli_prepare($conn, "SELECT profile_image FROM users WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$image_path = null;
if ($result && mysqli_num_rows($result) === 1) {
    $row = mysqli_fetch_assoc($result);
    $image_path = $row['profile_image'];
}
mysqli_stmt_close($stmt);

if (!$image_path) {
    http_response_code(404);
    exit;
}

$file_path = __DIR__ . '/uploads/profile_pictures/' . basename($image_path);

if (!file_exists($file_path) || !is_file($file_path)) {
    http_response_code(404);
    exit;
}

// Determine MIME type from extension
$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$mime_types = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];

$mime = $mime_types[$ext] ?? 'image/jpeg';

// Serve the image with caching
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . filesize($file_path));

readfile($file_path);
exit;
