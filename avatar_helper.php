<?php
/**
 * Render a user's avatar (profile picture or initials fallback).
 *
 * @param string|null $profile_image  The profile_image value from DB (filename or NULL)
 * @param string      $name           The user's full name (used for initials)
 * @param int         $user_id        The user's ID (used to build serve_avatar URL)
 * @param string      $size           'sm' (40px topbar), 'md' (56px sidebar), or 'lg' (100px profile)
 * @return string      HTML for the avatar
 */
function render_avatar(?string $profile_image, string $name, $user_id = 0, string $size = 'sm'): string
{
    $user_id = (int) $user_id;
    $initials = compute_initials($name ?? '');

    // Compute size from size class
    $sizes = [
        'sm' => ['class' => 'profile-avatar',   'css' => 'width:40px;height:40px;font-size:14px;'],
        'md' => ['class' => 'member-avatar',    'css' => 'width:56px;height:56px;font-size:16px;'],
        'lg' => ['class' => 'large-profile-avatar', 'css' => 'width:100px;height:100px;font-size:30px;'],
    ];
    $s = $sizes[$size] ?? $sizes['sm'];

    $escaped_initials = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');

    if ($profile_image && $user_id > 0) {
        $avatar_url = 'serve_avatar.php?id=' . $user_id;
        return '<div class="' . $s['class'] . ' has-image" style="' . $s['css'] . '">'
             . '<img src="' . htmlspecialchars($avatar_url, ENT_QUOTES, 'UTF-8') . '" '
             . 'alt="' . $escaped_initials . '" '
             . 'style="width:100%;height:100%;border-radius:50%;object-fit:cover;">'
             . '</div>';
    }

    return '<div class="' . $s['class'] . '" style="' . $s['css'] . '">'
         . $escaped_initials
         . '</div>';
}


/**
 * Compute initials from a full name.
 */
function compute_initials(string $name): string
{
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}
